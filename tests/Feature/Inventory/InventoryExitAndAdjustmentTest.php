<?php

namespace Tests\Feature\Inventory;

use App\Exceptions\InventoryException;
use App\Models\InventoryMovement;
use App\Models\Part;
use App\Services\InventoryService;

class InventoryExitAndAdjustmentTest extends InventoryTestCase
{
    public function test_exit_cannot_exceed_available_stock(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->entry($part['id'], 3, 100)->assertCreated();

        $this->exit($part['id'], 4)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INSUFFICIENT_STOCK')
            ->assertJsonStructure(['errors' => ['quantity']]);

        $this->assertSame(1, InventoryMovement::count());
        $this->assertSame('3.000', $this->stockOf($part['id'])->quantity_on_hand);
    }

    public function test_supplier_return_requires_supplier(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $supplier = $this->createSupplier();

        $this->entry($part['id'], 3, 100)->assertCreated();

        $this->exit($part['id'], 1, ['type' => 'supplier_return'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['supplier_id']);

        $this->exit($part['id'], 1, ['type' => 'supplier_return', 'supplier_id' => $supplier->id])
            ->assertCreated()
            ->assertJsonPath('data.type.value', 'supplier_return')
            ->assertJsonPath('data.type.direction', 'out');
    }

    public function test_exit_requires_reason_and_manual_type(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->postJson('/api/inventory/exits', [
            'part_id' => $part['id'],
            'type' => 'work_order_out',
            'quantity' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'reason']);
    }

    public function test_adjustment_registers_the_difference_from_physical_count(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->entry($part['id'], 10, 100)->assertCreated();

        $this->adjustment($part['id'], 7)
            ->assertCreated()
            ->assertJsonPath('data.type.value', 'adjustment_out')
            ->assertJsonPath('data.quantity', '-3.000')
            ->assertJsonPath('data.balance_after', '7.000')
            ->assertJsonPath('data.reason', 'Conteo físico mensual');

        $this->adjustment($part['id'], 9, ['unit_cost' => 130])
            ->assertCreated()
            ->assertJsonPath('data.type.value', 'adjustment_in')
            ->assertJsonPath('data.quantity', '2.000')
            ->assertJsonPath('data.balance_after', '9.000')
            // (7 × 100 + 2 × 130) / 9 = 106.666… → 106.6667
            ->assertJsonPath('data.average_cost_after', '106.6667');

        $this->adjustment($part['id'], 9)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVENTORY_NO_DIFFERENCE');

        $this->adjustment($part['id'], 0)
            ->assertCreated()
            ->assertJsonPath('data.balance_after', '0.000');
    }

    public function test_positive_adjustment_without_cost_uses_current_average(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->entry($part['id'], 4, 250)->assertCreated();

        $this->adjustment($part['id'], 5)
            ->assertCreated()
            ->assertJsonPath('data.unit_cost', '250.0000')
            ->assertJsonPath('data.average_cost_after', '250.0000');
    }

    public function test_adjustment_is_allowed_on_discontinued_part(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->entry($part['id'], 4, 100)->assertCreated();

        $this->postJson("/api/parts/{$part['id']}/discontinue")->assertOk();

        $this->adjustment($part['id'], 5)->assertCreated();
    }

    public function test_reservations_limit_exits_and_adjustments(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->entry($part['id'], 10, 100)->assertCreated();

        $service = app(InventoryService::class);
        $service->reserve(Part::find($part['id']), 6);

        $this->getJson("/api/parts/{$part['id']}")
            ->assertJsonPath('data.stock.on_hand', '10.000')
            ->assertJsonPath('data.stock.reserved', '6.000')
            ->assertJsonPath('data.stock.available', '4.000');

        $this->exit($part['id'], 5)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INSUFFICIENT_STOCK');

        $this->adjustment($part['id'], 5)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'STOCK_BELOW_RESERVED');

        $this->exit($part['id'], 4)->assertCreated();

        $service->release(Part::find($part['id']), 6);

        $this->assertSame('0.000', $this->stockOf($part['id'])->quantity_reserved);
    }

    public function test_cannot_reserve_more_than_available_or_release_more_than_reserved(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->entry($part['id'], 2, 100)->assertCreated();

        $service = app(InventoryService::class);
        $model = Part::find($part['id']);

        try {
            $service->reserve($model, 3);
            $this->fail('Se esperaba INSUFFICIENT_STOCK.');
        } catch (InventoryException $e) {
            $this->assertSame('INSUFFICIENT_STOCK', $e->getErrorCode());
        }

        $service->reserve($model, 1);

        try {
            $service->release($model, 2);
            $this->fail('Se esperaba INVENTORY_RELEASE_EXCEEDS_RESERVED.');
        } catch (InventoryException $e) {
            $this->assertSame('INVENTORY_RELEASE_EXCEEDS_RESERVED', $e->getErrorCode());
        }

        $this->assertSame('1.000', $this->stockOf($part['id'])->quantity_reserved);
    }
}
