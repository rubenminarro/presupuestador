<?php

namespace Tests\Feature\Purchases;

use App\Models\SupplierPayment;
use Tests\Feature\Inventory\InventoryTestCase;

class SupplierPaymentTest extends InventoryTestCase
{
    /**
     * Compra recibida exenta de una línea: total = cantidad × costo.
     */
    private function receivedPurchase(int|string $total = 100000, array $overrides = []): array
    {
        $part = $this->createPartViaApi();

        return $this->purchase($part['id'], 1, $total, $overrides)->assertOk()->json('data');
    }

    private function pay(int $purchaseId, array $overrides = [])
    {
        return $this->postJson("/api/purchases/{$purchaseId}/payments", [
            'amount' => 40000,
            'method' => 'cash',
            ...$overrides,
        ]);
    }

    public function test_partial_and_full_payments_update_balance_and_status(): void
    {
        $this->actingAsAdmin();

        $purchase = $this->receivedPurchase(100000);

        $this->assertSame('unpaid', $purchase['payment_summary']['status']);
        $this->assertSame('100000.00', $purchase['payment_summary']['balance']);

        $first = $this->pay($purchase['id'])
            ->assertCreated()
            ->assertJsonPath('data.code', 'PAG-000001')
            ->assertJsonPath('data.supplier.id', $purchase['supplier']['id'])
            ->assertJsonPath('data.purchase.balance', '60000.00')
            ->assertJsonPath('data.purchase.payment_status', 'partial')
            ->json('data');

        $this->assertSame(now()->toDateString(), $first['payment_date']);

        $this->pay($purchase['id'], ['amount' => 60001, 'method' => 'transfer', 'reference' => 'OP-1', 'bank' => 'Itaú'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'SUPPLIER_PAYMENT_EXCEEDS_BALANCE');

        $this->pay($purchase['id'], [
            'amount' => 60000,
            'method' => 'check',
            'reference' => 'CH-778',
            'bank' => 'Banco Basa',
            'supplier_receipt_number' => 'R-0099',
        ])
            ->assertCreated()
            ->assertJsonPath('data.bank', 'Banco Basa')
            ->assertJsonPath('data.supplier_receipt_number', 'R-0099')
            ->assertJsonPath('data.purchase.payment_status', 'paid');

        $this->pay($purchase['id'], ['amount' => 1])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PURCHASE_ALREADY_PAID');

        // La compra muestra sus pagos y saldo.
        $this->getJson("/api/purchases/{$purchase['id']}")
            ->assertOk()
            ->assertJsonPath('data.payment_summary.paid_total', '100000.00')
            ->assertJsonPath('data.payment_summary.balance', '0.00')
            ->assertJsonCount(2, 'data.payments');
        $this->getJson("/api/purchases?supplier_id={$purchase['supplier']['id']}")
            ->assertJsonPath('data.0.paid_total', '100000.00')
            ->assertJsonPath('data.0.payment_status', 'paid');
        $this->getJson("/api/purchases/{$purchase['id']}/payments")->assertOk()->assertJsonCount(2, 'data');

        // Anular un pago repone el saldo.
        $cancel = "/api/supplier-payments/{$first['id']}/cancel";
        $this->postJson($cancel, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson($cancel, ['reason' => 'Cargado dos veces'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'cancelled')
            ->assertJsonPath('data.purchase.balance', '40000.00')
            ->assertJsonPath('data.purchase.payment_status', 'partial');
        $this->postJson($cancel, ['reason' => 'Otra vez'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'SUPPLIER_PAYMENT_NOT_CANCELLABLE');
    }

    public function test_only_received_purchases_are_payable(): void
    {
        $this->actingAsAdmin();

        $draft = $this->postJson('/api/purchases', ['supplier_id' => $this->createSupplier()->id])->json('data');

        $this->pay($draft['id'])->assertStatus(422)->assertJsonPath('error_code', 'PURCHASE_NOT_PAYABLE');
        $this->assertNull($draft['payment_summary']['status']);

        $purchase = $this->receivedPurchase();
        $this->postJson("/api/purchases/{$purchase['id']}/cancel", ['reason' => 'Factura errónea'])->assertOk();

        $this->pay($purchase['id'])->assertStatus(422)->assertJsonPath('error_code', 'PURCHASE_NOT_PAYABLE');
    }

    public function test_purchase_with_payments_cannot_be_cancelled(): void
    {
        $this->actingAsAdmin();

        $purchase = $this->receivedPurchase();
        $payment = $this->pay($purchase['id'])->assertCreated()->json('data');

        $this->postJson("/api/purchases/{$purchase['id']}/cancel", ['reason' => 'Factura errónea'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PURCHASE_HAS_PAYMENTS');

        $this->postJson("/api/supplier-payments/{$payment['id']}/cancel", ['reason' => 'Revertir'])->assertOk();

        $this->postJson("/api/purchases/{$purchase['id']}/cancel", ['reason' => 'Factura errónea'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'cancelled');
    }

    public function test_payment_date_and_method_rules(): void
    {
        $this->actingAsAdmin();

        $purchase = $this->receivedPurchase(100000, ['supplier_document_date' => now()->subDays(5)->toDateString()]);

        $this->pay($purchase['id'], ['payment_date' => now()->subDays(6)->toDateString()])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'SUPPLIER_PAYMENT_DATE_BEFORE_DOCUMENT');

        $this->pay($purchase['id'], ['amount' => 1.5, 'method' => 'x', 'payment_date' => now()->addDay()->toDateString()])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'method', 'payment_date']);

        $this->pay($purchase['id'], ['method' => 'check'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reference', 'bank']);

        // En efectivo no se guardan referencia ni banco.
        $this->pay($purchase['id'], [
            'payment_date' => now()->subDays(5)->toDateString(),
            'reference' => 'X-1',
            'bank' => 'Banco',
        ])
            ->assertCreated()
            ->assertJsonPath('data.reference', null)
            ->assertJsonPath('data.bank', null);
    }

    public function test_payment_is_immutable(): void
    {
        $this->actingAsAdmin();

        $purchase = $this->receivedPurchase();
        $payment = SupplierPayment::findOrFail($this->pay($purchase['id'])->json('data.id'));

        $this->expectExceptionMessage('El pago al proveedor no puede modificarse; solo puede anularse.');
        $payment->update(['amount' => 1]);
    }

    public function test_index_and_summary(): void
    {
        $this->actingAsAdmin();

        $supplier = $this->createSupplier();
        $first = $this->receivedPurchase(100000, ['supplier_id' => $supplier->id]);
        $second = $this->receivedPurchase(50000);

        $this->pay($first['id'], ['amount' => 30000]);
        $this->pay($first['id'], ['amount' => 20000, 'method' => 'transfer', 'reference' => 'OP-55']);
        $cancelled = $this->pay($second['id'], ['amount' => 10000])->json('data.id');
        $this->postJson("/api/supplier-payments/{$cancelled}/cancel", ['reason' => 'Error'])->assertOk();

        $this->getJson('/api/supplier-payments')->assertOk()->assertJsonPath('meta.pagination.total', 3);
        $this->getJson("/api/supplier-payments?supplier_id={$supplier->id}")->assertJsonCount(2, 'data');
        $this->getJson("/api/supplier-payments?purchase_id={$second['id']}")->assertJsonCount(1, 'data');
        $this->getJson('/api/supplier-payments?method=transfer')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.purchase_code', $first['code']);
        $this->getJson('/api/supplier-payments?status=cancelled')->assertJsonCount(1, 'data');
        $this->getJson('/api/supplier-payments?search=OP-55')->assertJsonCount(1, 'data');
        $this->getJson("/api/supplier-payments/{$cancelled}")->assertOk()->assertJsonPath('data.cancellation_reason', 'Error');
        $this->getJson('/api/supplier-payments?status=x')->assertStatus(422)->assertJsonValidationErrors(['status']);

        $summary = $this->getJson('/api/supplier-payments/summary')->assertOk()->json('data');

        $this->assertSame(2, $summary['payments_count']);
        $this->assertSame('50000.00', $summary['total']);
        $this->assertSame('30000.00', collect($summary['by_method'])->firstWhere('method', 'cash')['total']);
        $this->assertSame('20000.00', collect($summary['by_method'])->firstWhere('method', 'transfer')['total']);
        $this->assertSame(1, $summary['cancelled_count']);
    }

    public function test_accounts_payable_splits_overdue_by_supplier(): void
    {
        $this->actingAsAdmin();

        $supplier = $this->createSupplier(['name' => 'Repuestos Sur']);

        // Crédito vencido, crédito por vencer y contado de hoy (vence hoy: no vencido).
        $overdue = $this->receivedPurchase(100000, [
            'supplier_id' => $supplier->id,
            'supplier_document_date' => now()->subDays(40)->toDateString(),
            'condition' => 'credit',
            'due_date' => now()->subDays(10)->toDateString(),
        ]);
        $this->receivedPurchase(70000, [
            'supplier_id' => $supplier->id,
            'condition' => 'credit',
            'due_date' => now()->addDays(20)->toDateString(),
        ]);
        $paid = $this->receivedPurchase(5000, ['supplier_id' => $supplier->id]);
        $this->pay($paid['id'], ['amount' => 5000])->assertCreated();
        $this->pay($overdue['id'], ['amount' => 25000])->assertCreated();

        $other = $this->receivedPurchase(30000);

        $data = $this->getJson('/api/accounts-payable')->assertOk()->json('data');

        $this->assertSame(now()->toDateString(), $data['as_of']);
        $this->assertSame('175000.00', $data['balance']);
        $this->assertSame('75000.00', $data['overdue']);
        $this->assertSame('100000.00', $data['not_due']);
        $this->assertCount(2, $data['suppliers']);

        $row = $data['suppliers'][0];
        $this->assertSame($supplier->id, $row['supplier_id']);
        $this->assertSame(2, $row['purchases_count']);
        $this->assertSame('145000.00', $row['balance']);
        $this->assertSame(now()->subDays(10)->toDateString(), $row['oldest_due_date']);
        $this->assertArrayNotHasKey('purchases', $row);

        $detail = $this->getJson("/api/accounts-payable?supplier_id={$supplier->id}")->assertOk()->json('data');

        $this->assertCount(1, $detail['suppliers']);
        $this->assertSame('145000.00', $detail['balance']);
        $this->assertSame($overdue['code'], $detail['suppliers'][0]['purchases'][0]['code']);
        $this->assertSame(10, $detail['suppliers'][0]['purchases'][0]['days_overdue']);
        $this->assertSame('partial', $detail['suppliers'][0]['purchases'][0]['payment_status']);
        $this->assertSame(0, $detail['suppliers'][0]['purchases'][1]['days_overdue']);

        $this->assertSame($other['supplier']['id'], $data['suppliers'][1]['supplier_id']);
        $this->getJson('/api/accounts-payable?supplier_id=999999')->assertStatus(422)->assertJsonValidationErrors(['supplier_id']);
    }

    public function test_supplier_payment_permissions_by_role(): void
    {
        $this->actingAsAdmin();
        $purchase = $this->receivedPurchase();

        $this->actingAsRole('cajero');
        $payment = $this->pay($purchase['id'])->assertCreated()->json('data');
        $this->getJson('/api/supplier-payments')->assertOk();
        $this->getJson('/api/supplier-payments/summary')->assertOk();
        $this->getJson('/api/accounts-payable')->assertOk();
        $this->postJson("/api/supplier-payments/{$payment['id']}/cancel", ['reason' => 'Error de carga'])->assertOk();

        foreach (['deposito', 'jefe-taller'] as $role) {
            $this->actingAsRole($role);
            $this->getJson('/api/supplier-payments')->assertOk();
            $this->getJson("/api/supplier-payments/{$payment['id']}")->assertOk();
            $this->getJson("/api/purchases/{$purchase['id']}/payments")->assertOk();
            $this->getJson('/api/accounts-payable')->assertOk();
            $this->pay($purchase['id'])->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
            $this->postJson("/api/supplier-payments/{$payment['id']}/cancel", ['reason' => 'x x x'])->assertForbidden();
            $this->getJson('/api/supplier-payments/summary')->assertForbidden();
        }

        $this->actingAsMechanic();
        $this->getJson('/api/supplier-payments')->assertForbidden();
        $this->getJson('/api/accounts-payable')->assertForbidden();
    }
}
