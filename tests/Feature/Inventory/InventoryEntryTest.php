<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryMovement;
use App\Models\PartStock;

class InventoryEntryTest extends InventoryTestCase
{
    public function test_new_part_is_created_with_empty_stock(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->assertDatabaseHas('part_stocks', [
            'part_id' => $part['id'],
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
        ]);

        $this->getJson("/api/parts/{$part['id']}")
            ->assertOk()
            ->assertJsonPath('data.stock.on_hand', '0.000')
            ->assertJsonPath('data.stock.available', '0.000')
            ->assertJsonPath('data.stock.is_below_minimum', true);
    }

    public function test_admin_can_register_initial_stock(): void
    {
        $user = $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->entry($part['id'], 10, 100, ['type' => 'initial'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type.value', 'initial')
            ->assertJsonPath('data.type.direction', 'in')
            ->assertJsonPath('data.quantity', '10.000')
            ->assertJsonPath('data.unit_cost', '100.0000')
            ->assertJsonPath('data.total_cost', '1000.00')
            ->assertJsonPath('data.balance_after', '10.000')
            ->assertJsonPath('data.average_cost_after', '100.0000')
            ->assertJsonPath('data.created_by.id', $user->id)
            ->assertJsonPath('data.warehouse.code', 'DEP-01');

        $stock = $this->stockOf($part['id']);

        $this->assertSame('10.000', $stock->quantity_on_hand);
        $this->assertSame('100.0000', $stock->average_cost);
        $this->assertNotNull($stock->last_movement_at);
    }

    public function test_initial_stock_can_only_be_registered_once(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->entry($part['id'], 5, 100)->assertCreated();

        $this->entry($part['id'], 10, 100, ['type' => 'initial'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'INVENTORY_INITIAL_ALREADY_REGISTERED');
    }

    public function test_weighted_average_cost_is_recalculated_on_entries_only(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->entry($part['id'], 10, 100, ['type' => 'initial'])->assertCreated();

        // (10 × 100 + 5 × 130) / 15 = 110
        $this->entry($part['id'], 5, 130)
            ->assertCreated()
            ->assertJsonPath('data.average_cost_after', '110.0000')
            ->assertJsonPath('data.balance_after', '15.000');

        // Las salidas usan el promedio vigente y no lo modifican.
        $this->exit($part['id'], 3)
            ->assertCreated()
            ->assertJsonPath('data.quantity', '-3.000')
            ->assertJsonPath('data.unit_cost', '110.0000')
            ->assertJsonPath('data.total_cost', '-330.00')
            ->assertJsonPath('data.average_cost_after', '110.0000')
            ->assertJsonPath('data.balance_after', '12.000');

        // (12 × 110 + 2 × 200) / 14 = 122.857142… → 122.8571
        $this->entry($part['id'], 2, 200)
            ->assertCreated()
            ->assertJsonPath('data.average_cost_after', '122.8571');

        $this->assertSame('122.8571', $this->stockOf($part['id'])->average_cost);
    }

    public function test_entry_after_stock_reaches_zero_takes_new_cost(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->entry($part['id'], 2, 100)->assertCreated();
        $this->exit($part['id'], 2)->assertCreated();

        $this->entry($part['id'], 1, 250)
            ->assertCreated()
            ->assertJsonPath('data.average_cost_after', '250.0000');
    }

    public function test_discontinued_part_rejects_entries_but_allows_exits(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->entry($part['id'], 5, 100)->assertCreated();

        $this->postJson("/api/parts/{$part['id']}/discontinue")->assertOk();

        $this->entry($part['id'], 1, 100)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PART_DISCONTINUED_NO_ENTRY');

        $this->exit($part['id'], 5)->assertCreated();
    }

    public function test_fractional_quantities_depend_on_part_unit(): void
    {
        $this->actingAsAdmin();

        $unitPart = $this->createPartViaApi(null, ['unit' => 'unit']);
        $literPart = $this->createPartViaApi(null, ['unit' => 'liter']);

        $this->entry($unitPart['id'], 1.5, 100)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVENTORY_FRACTIONAL_QUANTITY_NOT_ALLOWED');

        $this->entry($literPart['id'], 1.725, 100)
            ->assertCreated()
            ->assertJsonPath('data.quantity', '1.725');
    }

    public function test_entry_validates_input(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->postJson('/api/inventory/entries', [
            'part_id' => 999999,
            'type' => 'work_order_return',
            'quantity' => 0,
            'unit_cost' => -1,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['part_id', 'type', 'quantity', 'unit_cost']);

        $this->entry($part['id'], 1.2345, 100)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        $this->assertSame(0, InventoryMovement::count());
    }

    public function test_purchase_updates_last_cost_of_linked_supplier(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $linked = $this->createSupplier();
        $unlinked = $this->createSupplier();

        $this->postJson("/api/parts/{$part['id']}/suppliers", [
            'supplier_id' => $linked->id,
            'last_cost' => 30000,
        ])->assertSuccessful();

        $this->entry($part['id'], 3, 36500.5, [
            'supplier_id' => $linked->id,
            'document_number' => 'A-0001-00000123',
        ])
            ->assertCreated()
            ->assertJsonPath('data.supplier.id', $linked->id)
            ->assertJsonPath('data.document_number', 'A-0001-00000123');

        $this->assertDatabaseHas('part_supplier', [
            'part_id' => $part['id'],
            'supplier_id' => $linked->id,
            'last_cost' => 36500.50,
        ]);

        // Un proveedor no vinculado no se vincula automáticamente.
        $this->entry($part['id'], 1, 100, ['supplier_id' => $unlinked->id])->assertCreated();

        $this->assertDatabaseMissing('part_supplier', [
            'part_id' => $part['id'],
            'supplier_id' => $unlinked->id,
        ]);
    }

    public function test_purchase_from_inactive_supplier_is_rejected(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $supplier = $this->createSupplier(['is_active' => false]);

        $this->entry($part['id'], 1, 100, ['supplier_id' => $supplier->id])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'SUPPLIER_INACTIVE');

        $this->assertSame('0.000', PartStock::where('part_id', $part['id'])->value('quantity_on_hand'));
    }
}
