<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryMovement;

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

        $this->purchase($part['id'], 5, 100)->assertOk();

        $this->entry($part['id'], 10, 100, ['type' => 'initial'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'INVENTORY_INITIAL_ALREADY_REGISTERED');
    }

    public function test_manual_purchase_entries_are_rejected(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        // Las compras entran solo por el módulo de compras.
        $this->entry($part['id'], 1, 100, ['type' => 'purchase'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['type']);

        $this->assertSame(0, InventoryMovement::count());
    }

    public function test_weighted_average_cost_is_recalculated_on_entries_only(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->entry($part['id'], 10, 100, ['type' => 'initial'])->assertCreated();

        // (10 × 100 + 5 × 130) / 15 = 110
        $this->purchase($part['id'], 5, 130)->assertOk();
        $this->assertSame('110.0000', $this->stockOf($part['id'])->average_cost);
        $this->assertSame('15.000', $this->stockOf($part['id'])->quantity_on_hand);

        // Las salidas usan el promedio vigente y no lo modifican.
        $this->exit($part['id'], 3)
            ->assertCreated()
            ->assertJsonPath('data.quantity', '-3.000')
            ->assertJsonPath('data.unit_cost', '110.0000')
            ->assertJsonPath('data.total_cost', '-330.00')
            ->assertJsonPath('data.average_cost_after', '110.0000')
            ->assertJsonPath('data.balance_after', '12.000');

        // (12 × 110 + 2 × 200) / 14 = 122.857142… → 122.8571
        $this->purchase($part['id'], 2, 200)->assertOk();

        $this->assertSame('122.8571', $this->stockOf($part['id'])->average_cost);
    }

    public function test_entry_after_stock_reaches_zero_takes_new_cost(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->purchase($part['id'], 2, 100)->assertOk();
        $this->exit($part['id'], 2)->assertCreated();

        $this->purchase($part['id'], 1, 250)->assertOk();

        $this->assertSame('250.0000', $this->stockOf($part['id'])->average_cost);
    }

    public function test_discontinued_part_rejects_entries_but_allows_exits(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->purchase($part['id'], 5, 100)->assertOk();

        $this->postJson("/api/parts/{$part['id']}/discontinue")->assertOk();

        $this->purchase($part['id'], 1, 100)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PART_DISCONTINUED_NO_ENTRY');

        $this->exit($part['id'], 5)->assertCreated();
    }

    public function test_fractional_quantities_depend_on_part_unit(): void
    {
        $this->actingAsAdmin();

        $unitPart = $this->createPartViaApi(null, ['unit' => 'unit']);
        $literPart = $this->createPartViaApi(null, ['unit' => 'liter']);

        $this->purchase($unitPart['id'], 1.5, 100)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVENTORY_FRACTIONAL_QUANTITY_NOT_ALLOWED');

        $this->purchase($literPart['id'], 1.725, 100)
            ->assertOk()
            ->assertJsonPath('data.items.0.quantity', '1.725');

        $this->assertSame('1.725', $this->stockOf($literPart['id'])->quantity_on_hand);
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
}
