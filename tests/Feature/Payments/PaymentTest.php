<?php

namespace Tests\Feature\Payments;

use App\Models\Payment;
use Tests\Feature\Invoices\InvoiceTestCase;

class PaymentTest extends InvoiceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
    }

    /**
     * Factura emitida (contado o crédito) de la OT de InvoiceTestCase.
     */
    private function issuedInvoice(string $condition = 'cash'): array
    {
        $invoice = $this->readyInvoice();
        $this->createStamp()->assertCreated();

        if ($condition === 'credit') {
            $this->patchJson("/api/invoices/{$invoice['id']}", [
                'condition' => 'credit',
                'due_date' => now()->addDays(30)->toDateString(),
            ])->assertOk();
        }

        return $this->postJson("/api/invoices/{$invoice['id']}/issue")->assertOk()->json('data');
    }

    private function pay(array $invoice, int $amount, array $overrides = [])
    {
        return $this->postJson("/api/invoices/{$invoice['id']}/payments", [
            'amount' => $amount,
            'method' => 'cash',
            ...$overrides,
        ]);
    }

    private function total(array $invoice): int
    {
        return (int) $invoice['totals']['total'];
    }

    public function test_partial_payments_until_the_invoice_is_paid(): void
    {
        $invoice = $this->issuedInvoice();
        $total = $this->total($invoice);

        $this->assertSame('unpaid', $invoice['payment_summary']['status']);

        $this->pay($invoice, 100000)
            ->assertCreated()
            ->assertJsonPath('data.receipt_number', null)
            ->assertJsonPath('data.invoice.payment_status', 'partial')
            ->assertJsonPath('data.invoice.balance', number_format($total - 100000, 2, '.', ''));

        // El resto con tarjeta.
        $this->pay($invoice, $total - 100000, ['method' => 'card', 'reference' => 'VOUCHER-123'])
            ->assertCreated()
            ->assertJsonPath('data.reference', 'VOUCHER-123')
            ->assertJsonPath('data.invoice.payment_status', 'paid')
            ->assertJsonPath('data.invoice.balance', '0.00');

        $this->pay($invoice, 1)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVOICE_ALREADY_PAID');

        $this->getJson("/api/invoices/{$invoice['id']}")
            ->assertOk()
            ->assertJsonCount(2, 'data.payments')
            ->assertJsonPath('data.payment_summary.paid_total', number_format($total, 2, '.', ''))
            ->assertJsonPath('data.payment_summary.status', 'paid');

        $this->getJson('/api/invoices')
            ->assertOk()
            ->assertJsonPath('data.0.balance', '0.00')
            ->assertJsonPath('data.0.payment_status', 'paid');
    }

    public function test_payment_cannot_exceed_the_balance(): void
    {
        $invoice = $this->issuedInvoice();

        $this->pay($invoice, $this->total($invoice) + 1)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PAYMENT_EXCEEDS_BALANCE');

        $this->assertSame(0, Payment::count());
    }

    public function test_only_issued_invoices_accept_payments(): void
    {
        $draft = $this->readyInvoice();

        $this->pay($draft, 1000)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVOICE_NOT_PAYABLE');

        $this->createStamp()->assertCreated();
        $this->postJson("/api/invoices/{$draft['id']}/issue")->assertOk();
        $this->postJson("/api/invoices/{$draft['id']}/cancel", ['reason' => 'Error de datos'])->assertOk();

        $this->pay($draft, 1000)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVOICE_NOT_PAYABLE');
    }

    public function test_payment_validation(): void
    {
        $invoice = $this->issuedInvoice();
        $url = "/api/invoices/{$invoice['id']}/payments";

        $this->postJson($url, [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['amount', 'method']);

        $this->postJson($url, ['amount' => 1000.5, 'method' => 'bitcoin'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'method']);

        $this->postJson($url, ['amount' => 0, 'method' => 'cash'])->assertJsonValidationErrors(['amount']);

        $this->postJson($url, ['amount' => 1000, 'method' => 'card'])->assertJsonValidationErrors(['reference']);
        $this->postJson($url, ['amount' => 1000, 'method' => 'transfer'])->assertJsonValidationErrors(['reference']);
        $this->postJson($url, ['amount' => 1000, 'method' => 'check', 'reference' => '0012345'])->assertJsonValidationErrors(['bank']);

        $this->postJson($url, ['amount' => 1000, 'method' => 'cash', 'payment_date' => now()->addDay()->toDateString()])
            ->assertJsonValidationErrors(['payment_date']);

        // Anterior a la emisión (hoy): regla de negocio.
        $this->postJson($url, ['amount' => 1000, 'method' => 'cash', 'payment_date' => now()->subDay()->toDateString()])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PAYMENT_DATE_BEFORE_ISSUE');

        $this->postJson($url, ['amount' => 1000, 'method' => 'check', 'reference' => '0012345', 'bank' => 'Banco Continental'])
            ->assertCreated()
            ->assertJsonPath('data.bank', 'Banco Continental')
            ->assertJsonPath('data.payment_date', now()->toDateString());

        // En efectivo la referencia se descarta.
        $this->postJson($url, ['amount' => 1000, 'method' => 'cash', 'reference' => 'X1'])
            ->assertCreated()
            ->assertJsonPath('data.reference', null);
    }

    public function test_credit_payments_get_sequential_receipts(): void
    {
        $invoice = $this->issuedInvoice('credit');

        $this->pay($invoice, 1000)->assertCreated()->assertJsonPath('data.receipt_number', 'REC-000001');
        $second = $this->pay($invoice, 2000, ['method' => 'transfer', 'reference' => 'OP-998'])
            ->assertCreated()
            ->assertJsonPath('data.receipt_number', 'REC-000002')
            ->json('data');

        // Un recibo anulado no se reutiliza.
        $this->postJson("/api/payments/{$second['id']}/cancel", ['reason' => 'Transferencia rechazada'])->assertOk();

        $this->pay($invoice, 3000)->assertCreated()->assertJsonPath('data.receipt_number', 'REC-000003');
    }

    public function test_cancelling_a_payment_restores_the_balance(): void
    {
        $invoice = $this->issuedInvoice();
        $total = $this->total($invoice);

        $payment = $this->pay($invoice, $total)->assertCreated()->json('data');

        $this->postJson("/api/payments/{$payment['id']}/cancel", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $this->postJson("/api/payments/{$payment['id']}/cancel", ['reason' => 'Monto mal cargado'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'cancelled')
            ->assertJsonPath('data.cancellation_reason', 'Monto mal cargado')
            ->assertJsonPath('data.invoice.payment_status', 'unpaid')
            ->assertJsonPath('data.invoice.balance', number_format($total, 2, '.', ''));

        $this->postJson("/api/payments/{$payment['id']}/cancel", ['reason' => 'Otra vez'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PAYMENT_NOT_CANCELLABLE');

        // El saldo vuelve a estar disponible.
        $this->pay($invoice, $total)->assertCreated();
    }

    public function test_invoice_with_payments_cannot_be_cancelled(): void
    {
        $invoice = $this->issuedInvoice();
        $payment = $this->pay($invoice, 1000)->assertCreated()->json('data');

        $this->postJson("/api/invoices/{$invoice['id']}/cancel", ['reason' => 'Error'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVOICE_HAS_PAYMENTS');

        $this->postJson("/api/payments/{$payment['id']}/cancel", ['reason' => 'Se anula la factura'])->assertOk();

        $this->postJson("/api/invoices/{$invoice['id']}/cancel", ['reason' => 'Error'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'cancelled');
    }

    public function test_payments_are_immutable(): void
    {
        $invoice = $this->issuedInvoice();
        $payment = Payment::findOrFail($this->pay($invoice, 1000)->json('data.id'));

        $this->expectExceptionMessage('solo puede anularse');

        $payment->update(['amount' => 1]);
    }

    public function test_listing_and_cash_summary(): void
    {
        $invoice = $this->issuedInvoice();

        $this->pay($invoice, 10000)->assertCreated();
        $this->pay($invoice, 20000, ['method' => 'card', 'reference' => 'V-1'])->assertCreated();
        $this->pay($invoice, 30000, ['method' => 'card', 'reference' => 'V-2'])->assertCreated();
        $cancelled = $this->pay($invoice, 5000)->assertCreated()->json('data');
        $this->postJson("/api/payments/{$cancelled['id']}/cancel", ['reason' => 'Duplicado'])->assertOk();

        $this->getJson('/api/payments?method=card')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.pagination.total', 2);

        $this->getJson('/api/payments?status=cancelled')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/payments/{$cancelled['id']}")->assertOk()->assertJsonPath('data.invoice.id', $invoice['id']);

        $summary = $this->getJson('/api/payments/summary')
            ->assertOk()
            ->assertJsonPath('data.payments_count', 3)
            ->assertJsonPath('data.total', '60000.00')
            ->assertJsonPath('data.cancelled_count', 1)
            ->json('data.by_method');

        $byMethod = collect($summary)->keyBy('method');

        $this->assertSame('10000.00', $byMethod['cash']['total']);
        $this->assertSame(2, $byMethod['card']['payments_count']);
        $this->assertSame('50000.00', $byMethod['card']['total']);
        $this->assertSame('0.00', $byMethod['check']['total']);

        $this->getJson('/api/payments/summary?date_from=2020-01-01&date_to=2020-01-31')
            ->assertOk()
            ->assertJsonPath('data.total', '0.00');

        $this->getJson('/api/payments/summary?date_from=2026-02-01&date_to=2026-01-01')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR');
    }

    public function test_mechanic_cannot_manage_payments(): void
    {
        $invoice = $this->issuedInvoice();
        $payment = $this->pay($invoice, 1000)->json('data');

        $this->actingAsMechanic();

        $this->pay($invoice, 1000)->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
        $this->getJson('/api/payments')->assertForbidden();
        $this->getJson('/api/payments/summary')->assertForbidden();
        $this->postJson("/api/payments/{$payment['id']}/cancel", ['reason' => 'x'])->assertForbidden();
    }
}
