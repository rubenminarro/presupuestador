<?php

namespace Tests\Feature\Purchases;

use App\Models\InventoryMovement;
use App\Models\PurchaseItem;
use Tests\Feature\Inventory\InventoryTestCase;

class PurchaseTest extends InventoryTestCase
{
    private function createPurchase(array $overrides = []): array
    {
        return $this->postJson('/api/purchases', [
            'supplier_id' => $overrides['supplier_id'] ?? $this->createSupplier()->id,
            'supplier_document_number' => '001-002-0000123',
            'supplier_document_date' => now()->toDateString(),
            ...$overrides,
        ])->assertCreated()->json('data');
    }

    private function addItem(int $purchaseId, int $partId, array $overrides = []): array
    {
        return $this->postJson("/api/purchases/{$purchaseId}/items", [
            'part_id' => $partId,
            'quantity' => 1,
            'unit_cost' => 11000,
            ...$overrides,
        ])->assertCreated()->json('data');
    }

    public function test_full_flow_computes_iva_and_enters_stock_at_net_cost(): void
    {
        $this->actingAsAdmin();

        $filter = $this->createPartViaApi();
        $oil = $this->createPartViaApi();
        $kit = $this->createPartViaApi();

        $purchase = $this->createPurchase();

        $this->assertSame('draft', $purchase['status']['value']);
        $this->assertStringStartsWith('COM-', $purchase['code']);

        // 10% por defecto: 2 × 11.000 = 22.000, sin IVA 10.000 c/u.
        $this->addItem($purchase['id'], $filter['id'], ['quantity' => 2, 'unit_cost' => 11000]);
        $this->addItem($purchase['id'], $oil['id'], ['unit_cost' => 21000, 'tax_rate' => 5]);
        $data = $this->addItem($purchase['id'], $kit['id'], ['quantity' => 3, 'unit_cost' => 1000, 'tax_rate' => 0]);

        $this->assertSame('10000.0000', $data['items'][0]['net_unit_cost']);
        $this->assertSame('22000.00', $data['items'][0]['total']);
        $this->assertSame('20000.0000', $data['items'][1]['net_unit_cost']);
        $this->assertSame([
            'exempt' => '3000.00',
            'taxed_5' => '21000.00',
            'taxed_10' => '22000.00',
            'iva_5' => '1000.00',
            'iva_10' => '2000.00',
            'iva_total' => '3000.00',
            'total' => '46000.00',
            'net_total' => '43000.00',
        ], $data['totals']);

        // Editar y quitar líneas en borrador.
        $kitItem = $data['items'][2]['id'];
        $this->patchJson("/api/purchases/{$purchase['id']}/items/{$kitItem}", ['quantity' => 4])
            ->assertOk()
            ->assertJsonPath('data.totals.exempt', '4000.00');
        $this->deleteJson("/api/purchases/{$purchase['id']}/items/{$kitItem}")
            ->assertOk()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.totals.total', '43000.00');

        $received = $this->postJson("/api/purchases/{$purchase['id']}/receive")
            ->assertOk()
            ->assertJsonPath('data.status.value', 'received')
            ->json('data');

        $this->assertSame('2.000', $this->stockOf($filter['id'])->quantity_on_hand);
        $this->assertSame('10000.0000', $this->stockOf($filter['id'])->average_cost);
        $this->assertSame('20000.0000', $this->stockOf($oil['id'])->average_cost);

        // Cada línea queda vinculada a su movimiento de compra.
        $movement = InventoryMovement::findOrFail($received['items'][0]['inventory_movement_id']);
        $this->assertSame('purchase', $movement->type->value);
        $this->assertSame('purchase_item', $movement->reference_type);
        $this->assertSame('001-002-0000123', $movement->document_number);

        // El proveedor queda vinculado al repuesto con el costo sin IVA.
        $this->assertDatabaseHas('part_supplier', [
            'part_id' => $filter['id'],
            'supplier_id' => $purchase['supplier']['id'],
            'last_cost' => 10000,
        ]);

        // Recibida: ya no se edita ni se elimina.
        $this->patchJson("/api/purchases/{$purchase['id']}", ['notes' => 'x'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PURCHASE_NOT_EDITABLE');
        $this->postJson("/api/purchases/{$purchase['id']}/items", ['part_id' => $kit['id'], 'quantity' => 1, 'unit_cost' => 1])
            ->assertJsonPath('error_code', 'PURCHASE_NOT_EDITABLE');
        $this->deleteJson("/api/purchases/{$purchase['id']}")
            ->assertJsonPath('error_code', 'PURCHASE_NOT_EDITABLE');
        $this->postJson("/api/purchases/{$purchase['id']}/receive")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PURCHASE_NOT_RECEIVABLE');
    }

    public function test_receiving_updates_last_cost_of_an_existing_link(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $supplier = $this->createSupplier();

        $this->postJson("/api/parts/{$part['id']}/suppliers", [
            'supplier_id' => $supplier->id,
            'last_cost' => 30000,
        ])->assertSuccessful();

        // 36.500 con IVA 10% → 33.181,8182 sin IVA → last_cost 33.181,82.
        $this->purchase($part['id'], 3, 36500, ['supplier_id' => $supplier->id, 'tax_rate' => 10])->assertOk();

        $this->assertDatabaseHas('part_supplier', [
            'part_id' => $part['id'],
            'supplier_id' => $supplier->id,
            'last_cost' => 33181.82,
        ]);
        $this->assertSame('33181.8182', $this->stockOf($part['id'])->average_cost);
    }

    public function test_receive_requires_items_document_and_due_date_on_credit(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $purchase = $this->createPurchase(['supplier_document_number' => null]);
        $receive = "/api/purchases/{$purchase['id']}/receive";

        $this->postJson($receive)->assertStatus(422)->assertJsonPath('error_code', 'PURCHASE_WITHOUT_ITEMS');

        $this->addItem($purchase['id'], $part['id']);

        $this->postJson($receive)->assertStatus(422)->assertJsonPath('error_code', 'PURCHASE_DOCUMENT_REQUIRED');

        $this->patchJson("/api/purchases/{$purchase['id']}", [
            'supplier_document_number' => '001-001-0000001',
            'condition' => 'credit',
        ])->assertOk();

        $this->postJson($receive)->assertStatus(422)->assertJsonPath('error_code', 'PURCHASE_DUE_DATE_INVALID');

        $this->patchJson("/api/purchases/{$purchase['id']}", ['due_date' => now()->subDay()->toDateString()])->assertOk();
        $this->postJson($receive)->assertJsonPath('error_code', 'PURCHASE_DUE_DATE_INVALID');

        $this->patchJson("/api/purchases/{$purchase['id']}", ['due_date' => now()->addDays(30)->toDateString()])->assertOk();
        $this->postJson($receive)->assertOk()->assertJsonPath('data.condition.value', 'credit');

        // Los intentos rechazados no movieron stock.
        $this->assertSame(1, InventoryMovement::where('type', 'purchase')->count());
    }

    public function test_supplier_document_cannot_be_received_twice(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $supplier = $this->createSupplier();

        $first = $this->createPurchase(['supplier_id' => $supplier->id]);
        $this->addItem($first['id'], $part['id']);
        $this->postJson("/api/purchases/{$first['id']}/receive")->assertOk();

        $second = $this->createPurchase(['supplier_id' => $supplier->id]);
        $this->addItem($second['id'], $part['id']);
        $this->postJson("/api/purchases/{$second['id']}/receive")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'PURCHASE_DUPLICATE_DOCUMENT');

        // El mismo número de otro proveedor sí se acepta.
        $other = $this->createPurchase();
        $this->addItem($other['id'], $part['id']);
        $this->postJson("/api/purchases/{$other['id']}/receive")->assertOk();

        // Anulada la primera, el número queda libre.
        $this->postJson("/api/purchases/{$first['id']}/cancel", ['reason' => 'Cargada con errores'])->assertOk();
        $this->postJson("/api/purchases/{$second['id']}/receive")->assertOk();
    }

    public function test_inactive_supplier_and_invalid_input_are_rejected(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->postJson('/api/purchases', ['supplier_id' => $this->createSupplier(['is_active' => false])->id])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'SUPPLIER_INACTIVE');

        $this->postJson('/api/purchases', [
            'supplier_document_date' => now()->addDay()->toDateString(),
            'condition' => 'x',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['supplier_id', 'supplier_document_date', 'condition']);

        $purchase = $this->createPurchase();

        $this->postJson("/api/purchases/{$purchase['id']}/items", ['part_id' => $part['id'], 'quantity' => 0, 'unit_cost' => -1, 'tax_rate' => 7])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['quantity', 'unit_cost', 'tax_rate']);

        // Proveedor desactivado después de crear la compra: no se recibe.
        $this->addItem($purchase['id'], $part['id']);
        $this->patchJson("/api/suppliers/{$purchase['supplier']['id']}", ['is_active' => false])->assertOk();

        $this->postJson("/api/purchases/{$purchase['id']}/receive")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'SUPPLIER_INACTIVE');
        $this->assertSame('0.000', $this->stockOf($part['id'])->quantity_on_hand);
    }

    public function test_cancel_reverses_stock_only_if_it_is_still_available(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $draft = $this->createPurchase();
        $this->postJson("/api/purchases/{$draft['id']}/cancel", ['reason' => 'No corresponde'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PURCHASE_NOT_CANCELLABLE');
        $this->deleteJson("/api/purchases/{$draft['id']}")->assertOk();
        $this->assertSoftDeleted('purchases', ['id' => $draft['id']]);

        $purchaseId = $this->purchase($part['id'], 5, 100)->assertOk()->json('data.id');

        // Se consumió parte del stock: no alcanza para revertir.
        $this->exit($part['id'], 2)->assertCreated();

        $cancel = "/api/purchases/{$purchaseId}/cancel";

        $this->postJson($cancel, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson($cancel, ['reason' => 'Factura duplicada'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INSUFFICIENT_STOCK');
        $this->assertSame('3.000', $this->stockOf($part['id'])->quantity_on_hand);

        $this->purchase($part['id'], 2, 100)->assertOk();

        $this->postJson($cancel, ['reason' => 'Factura duplicada'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'cancelled')
            ->assertJsonPath('data.cancellation_reason', 'Factura duplicada');

        $this->assertSame('0.000', $this->stockOf($part['id'])->quantity_on_hand);

        $reversal = InventoryMovement::where('type', 'purchase_cancellation')->firstOrFail();
        $this->assertSame('-5.000', $reversal->quantity);
        $this->assertSame(PurchaseItem::where('purchase_id', $purchaseId)->value('id'), $reversal->reference_id);

        $this->postJson($cancel, ['reason' => 'Otra vez'])->assertJsonPath('error_code', 'PURCHASE_NOT_CANCELLABLE');
    }

    public function test_index_filters_purchases(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $supplier = $this->createSupplier();

        $received = $this->purchase($part['id'], 1, 100, ['supplier_id' => $supplier->id])->json('data');
        $this->createPurchase(['supplier_document_number' => 'B-999']);

        $this->getJson('/api/purchases')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.pagination.total', 2);
        $this->getJson('/api/purchases?status=received')->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', $received['code']);
        $this->getJson("/api/purchases?supplier_id={$supplier->id}")->assertJsonCount(1, 'data');
        $this->getJson('/api/purchases?search=B-999')->assertJsonCount(1, 'data');
        $this->getJson("/api/purchases/{$received['id']}")->assertOk()->assertJsonPath('data.supplier.id', $supplier->id);
        $this->getJson('/api/purchases?status=paid')->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    public function test_purchase_permissions_by_role(): void
    {
        $this->actingAsAdmin();
        $purchase = $this->createPurchase();

        $this->actingAsRole('deposito');
        $this->createPurchase();
        $this->postJson("/api/purchases/{$purchase['id']}/receive")->assertStatus(422)->assertJsonPath('error_code', 'PURCHASE_WITHOUT_ITEMS');

        $this->actingAsRole('cajero');
        $this->getJson('/api/purchases')->assertOk();
        $this->getJson("/api/purchases/{$purchase['id']}")->assertOk();
        $this->postJson('/api/purchases', [])->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
        $this->postJson("/api/purchases/{$purchase['id']}/receive")->assertForbidden();

        $this->actingAsRole('jefe-taller');
        $this->getJson('/api/purchases')->assertOk();
        $this->postJson("/api/purchases/{$purchase['id']}/cancel", ['reason' => 'x x x'])->assertForbidden();

        $this->actingAsMechanic();
        $this->getJson('/api/purchases')->assertForbidden();
    }
}
