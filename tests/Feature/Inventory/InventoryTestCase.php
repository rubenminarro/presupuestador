<?php

namespace Tests\Feature\Inventory;

use App\Models\PartStock;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Parts\PartsTestCase;

abstract class InventoryTestCase extends PartsTestCase
{
    private int $purchaseDocument = 0;

    /**
     * Entrada manual (POST /inventory/entries): hoy solo stock inicial.
     */
    protected function entry(int $partId, float|int|string $quantity, float|int|string $unitCost, array $overrides = []): TestResponse
    {
        return $this->postJson('/api/inventory/entries', [
            'part_id' => $partId,
            'type' => 'initial',
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            ...$overrides,
        ]);
    }

    /**
     * Ingreso de stock por una compra recibida de una sola línea, exenta de IVA
     * (el costo que entra al inventario es el mismo $unitCost).
     * Devuelve la respuesta de POST /purchases/{id}/receive (200 si entró).
     */
    protected function purchase(int $partId, float|int|string $quantity, float|int|string $unitCost, array $overrides = []): TestResponse
    {
        $supplierId = $overrides['supplier_id'] ?? $this->createSupplier()->id;

        $purchaseId = $this->postJson('/api/purchases', [
            'supplier_id' => $supplierId,
            'supplier_document_number' => $overrides['supplier_document_number'] ?? '001-001-' . str_pad((string) ++$this->purchaseDocument, 7, '0', STR_PAD_LEFT),
            'supplier_document_date' => $overrides['supplier_document_date'] ?? now()->toDateString(),
            'condition' => $overrides['condition'] ?? 'cash',
            'due_date' => $overrides['due_date'] ?? null,
        ])->assertCreated()->json('data.id');

        $item = $this->postJson("/api/purchases/{$purchaseId}/items", [
            'part_id' => $partId,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'tax_rate' => $overrides['tax_rate'] ?? 0,
        ]);

        if (!$item->isSuccessful()) {
            return $item;
        }

        return $this->postJson("/api/purchases/{$purchaseId}/receive");
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
