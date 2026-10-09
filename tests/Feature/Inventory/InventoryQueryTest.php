<?php

namespace Tests\Feature\Inventory;

use App\Exceptions\InventoryException;
use App\Models\InventoryMovement;

class InventoryQueryTest extends InventoryTestCase
{
    public function test_movement_history_is_consistent_with_stock_balance(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi(null, ['unit' => 'liter']);

        $this->entry($part['id'], 20, 50, ['type' => 'initial'])->assertCreated();
        $this->exit($part['id'], 1.7)->assertCreated();
        $this->entry($part['id'], 4.25, 60)->assertCreated();
        $this->adjustment($part['id'], 22)->assertCreated();
        $this->exit($part['id'], 0.5)->assertCreated();

        $movements = InventoryMovement::where('part_id', $part['id'])->orderBy('id')->get();

        $running = '0';

        foreach ($movements as $movement) {
            $running = bcadd($running, $movement->quantity, 3);
            $this->assertSame($running, $movement->balance_after);
        }

        $this->assertSame($running, $this->stockOf($part['id'])->quantity_on_hand);
        $this->assertSame('21.500', $running);
    }

    public function test_part_movements_endpoint_returns_history_newest_first(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $other = $this->createPartViaApi();

        $this->entry($part['id'], 5, 100, ['type' => 'initial'])->assertCreated();
        $this->exit($part['id'], 2)->assertCreated();
        $this->entry($other['id'], 1, 100)->assertCreated();

        $this->getJson("/api/parts/{$part['id']}/movements")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type.value', 'loss')
            ->assertJsonPath('data.1.type.value', 'initial')
            ->assertJsonPath('meta.pagination.total', 2);
    }

    public function test_movements_can_be_filtered_and_shown(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->entry($part['id'], 5, 100)->assertCreated();
        $exitId = $this->exit($part['id'], 1)->assertCreated()->json('data.id');

        $this->getJson('/api/inventory/movements?type=loss')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $exitId)
            ->assertJsonPath('data.0.part.code', $part['code']);

        $today = now()->toDateString();

        $this->getJson("/api/inventory/movements?date_from={$today}&date_to={$today}")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/inventory/movements?date_from=ayer&type=otro')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date_from', 'type']);

        $this->getJson("/api/inventory/movements/{$exitId}")
            ->assertOk()
            ->assertJsonPath('data.reason', 'Rotura en depósito')
            ->assertJsonPath('data.part.id', $part['id']);

        $this->getJson('/api/inventory/movements/999999')
            ->assertNotFound()
            ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');
    }

    public function test_stock_listing_supports_low_stock_and_with_stock_filters(): void
    {
        $this->actingAsAdmin();

        $low = $this->createPartViaApi(null, ['name' => 'Filtro de aire', 'minimum_stock' => 5]);
        $ok = $this->createPartViaApi(null, ['name' => 'Bujia', 'minimum_stock' => 2]);
        $empty = $this->createPartViaApi(null, ['name' => 'Correa', 'minimum_stock' => 0]);
        $deleted = $this->createPartViaApi(null, ['name' => 'Lampara']);

        $this->entry($low['id'], 3, 100)->assertCreated();
        $this->entry($ok['id'], 10, 100)->assertCreated();
        $this->deleteJson("/api/parts/{$deleted['id']}")->assertOk();

        $this->getJson('/api/inventory/stocks')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.part.name', 'Bujia')
            ->assertJsonPath('meta.pagination.total', 3);

        $this->getJson('/api/inventory/stocks?below_minimum=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.part_id', $low['id'])
            ->assertJsonPath('data.0.is_below_minimum', true)
            ->assertJsonPath('data.0.quantity_available', '3.000')
            ->assertJsonPath('data.0.stock_value', '300.00');

        $this->getJson('/api/inventory/stocks?with_stock=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/inventory/stocks?search=Correa')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.part_id', $empty['id']);
    }

    public function test_movements_are_immutable(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $id = $this->entry($part['id'], 5, 100)->assertCreated()->json('data.id');

        $movement = InventoryMovement::findOrFail($id);

        try {
            $movement->update(['quantity' => 500]);
            $this->fail('Se esperaba INVENTORY_MOVEMENT_IMMUTABLE.');
        } catch (InventoryException $e) {
            $this->assertSame('INVENTORY_MOVEMENT_IMMUTABLE', $e->getErrorCode());
        }

        try {
            $movement->delete();
            $this->fail('Se esperaba INVENTORY_MOVEMENT_IMMUTABLE.');
        } catch (InventoryException $e) {
            $this->assertSame('INVENTORY_MOVEMENT_IMMUTABLE', $e->getErrorCode());
        }

        $this->assertSame('5.000', InventoryMovement::findOrFail($id)->quantity);
    }

    public function test_part_with_stock_cannot_be_deleted(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->entry($part['id'], 2, 100)->assertCreated();

        $this->deleteJson("/api/parts/{$part['id']}")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'PART_HAS_STOCK');

        $this->exit($part['id'], 2)->assertCreated();

        $this->deleteJson("/api/parts/{$part['id']}")->assertOk();

        // El historial sigue accesible aunque el repuesto esté eliminado.
        $this->getJson('/api/inventory/movements')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.part.code', $part['code']);
    }

    public function test_mechanic_can_query_but_not_register_movements(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $movementId = $this->entry($part['id'], 5, 100)->json('data.id');

        $this->actingAsMechanic();

        $this->getJson('/api/inventory/stocks')->assertOk();
        $this->getJson('/api/inventory/movements')->assertOk();
        $this->getJson("/api/inventory/movements/{$movementId}")->assertOk();
        $this->getJson("/api/parts/{$part['id']}/movements")->assertOk();

        $this->entry($part['id'], 1, 100)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');
        $this->exit($part['id'], 1)->assertForbidden();
        $this->adjustment($part['id'], 1)->assertForbidden();
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/inventory/stocks')
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');
    }
}
