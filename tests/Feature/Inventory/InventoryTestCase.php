<?php

namespace Tests\Feature\Inventory;

use App\Models\PartStock;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Parts\PartsTestCase;

abstract class InventoryTestCase extends PartsTestCase
{
    protected function entry(int $partId, float|int|string $quantity, float|int|string $unitCost, array $overrides = []): TestResponse
    {
        return $this->postJson('/api/inventory/entries', [
            'part_id' => $partId,
            'type' => 'purchase',
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            ...$overrides,
        ]);
    }

    protected function exit(int $partId, float|int|string $quantity, array $overrides = []): TestResponse
    {
        return $this->postJson('/api/inventory/exits', [
            'part_id' => $partId,
            'type' => 'loss',
            'quantity' => $quantity,
            'reason' => 'Rotura en depósito',
            ...$overrides,
        ]);
    }

    protected function adjustment(int $partId, float|int|string $counted, array $overrides = []): TestResponse
    {
        return $this->postJson('/api/inventory/adjustments', [
            'part_id' => $partId,
            'counted_quantity' => $counted,
            'reason' => 'Conteo físico mensual',
            ...$overrides,
        ]);
    }

    protected function stockOf(int $partId): PartStock
    {
        return PartStock::where('part_id', $partId)->firstOrFail();
    }
}
