<?php

namespace Tests\Feature\Invoices;

use App\Exceptions\InvoiceException;
use App\Models\Invoice;

class InvoiceIssueTest extends InvoiceTestCase
{
    public function test_issue_assigns_sequential_numbers_from_the_stamp(): void
    {
        $this->actingAsAdmin();

        $this->createStamp()->assertCreated()->assertJsonPath('data.next_invoice_number', '001-001-0000001');

        $first = $this->readyInvoice();
        $second = $this->readyInvoice();

        $this->postJson("/api/invoices/{$first['id']}/issue")
            ->assertOk()
            ->assertJsonPath('data.status.value', 'issued')
            ->assertJsonPath('data.number', '001-001-0000001')
            ->assertJsonPath('data.stamp.stamp_number', '12345678')
            ->assertJsonPath('data.issue_date', now()->toDateString());

        $this->postJson("/api/invoices/{$second['id']}/issue")
            ->assertOk()
            ->assertJsonPath('data.number', '001-001-0000002');

        $this->getJson('/api/invoice-stamps')
            ->assertJsonPath('data.0.next_number', 3);

        $this->postJson("/api/invoices/{$first['id']}/issue")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVOICE_NOT_ISSUABLE');
    }

    public function test_issue_requirements(): void
    {
        $this->actingAsAdmin();

        $invoice = $this->generateInvoice($this->completedWorkOrder());
        $issue = "/api/invoices/{$invoice['id']}/issue";

        // Sin timbrado.
        $this->patchJson("/api/invoices/{$invoice['id']}", ['customer_tax_id' => '80012345-6'])->assertOk();
        $this->postJson($issue)->assertStatus(422)->assertJsonPath('error_code', 'INVOICE_STAMP_NOT_AVAILABLE');

        $this->createStamp()->assertCreated();

        // Sin RUC/CI (el cliente de prueba no tiene documento).
        $this->patchJson("/api/invoices/{$invoice['id']}", ['customer_tax_id' => null])->assertOk();
        $this->postJson($issue)->assertStatus(422)->assertJsonPath('error_code', 'INVOICE_CUSTOMER_TAX_ID_REQUIRED');

        // Crédito sin vencimiento o vencido.
        $this->patchJson("/api/invoices/{$invoice['id']}", ['customer_tax_id' => '80012345-6', 'condition' => 'credit'])->assertOk();
        $this->postJson($issue)->assertStatus(422)->assertJsonPath('error_code', 'INVOICE_DUE_DATE_REQUIRED');

        $this->patchJson("/api/invoices/{$invoice['id']}", ['due_date' => now()->subDay()->toDateString()])->assertOk();
        $this->postJson($issue)->assertStatus(422)->assertJsonPath('error_code', 'INVOICE_DUE_DATE_REQUIRED');

        $this->patchJson("/api/invoices/{$invoice['id']}", ['due_date' => now()->addDays(30)->toDateString()])->assertOk();
        $this->postJson($issue)->assertOk()->assertJsonPath('data.condition.value', 'credit');
    }

    public function test_issued_invoice_is_immutable(): void
    {
        $this->actingAsAdmin();

        $this->createStamp()->assertCreated();
        $invoice = $this->readyInvoice();
        $this->postJson("/api/invoices/{$invoice['id']}/issue")->assertOk();

        $this->patchJson("/api/invoices/{$invoice['id']}", ['customer_name' => 'Otro'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVOICE_NOT_EDITABLE');

        $this->patchJson("/api/invoices/{$invoice['id']}/items/{$invoice['items'][0]['id']}", ['discount' => 1])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVOICE_NOT_EDITABLE');

        $this->deleteJson("/api/invoices/{$invoice['id']}")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVOICE_NOT_EDITABLE');

        // También a nivel de modelo.
        try {
            Invoice::findOrFail($invoice['id'])->update(['total' => 1]);
            $this->fail('Se esperaba INVOICE_NOT_EDITABLE.');
        } catch (InvoiceException $e) {
            $this->assertSame('INVOICE_NOT_EDITABLE', $e->getErrorCode());
        }
    }

    public function test_cancellation_frees_the_work_order_without_reusing_the_number(): void
    {
        $this->actingAsAdmin();

        $this->createStamp()->assertCreated();

        $workOrder = $this->completedWorkOrder();
        $invoice = $this->generateInvoice($workOrder);
        $cancel = "/api/invoices/{$invoice['id']}/cancel";

        $this->postJson($cancel, ['reason' => 'Prueba'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVOICE_NOT_CANCELLABLE');

        $this->patchJson("/api/invoices/{$invoice['id']}", ['customer_tax_id' => '80012345-6'])->assertOk();
        $this->postJson("/api/invoices/{$invoice['id']}/issue")->assertOk();

        $this->postJson($cancel, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);

        $this->postJson($cancel, ['reason' => 'RUC del cliente incorrecto'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'cancelled')
            ->assertJsonPath('data.number', '001-001-0000001')
            ->assertJsonPath('data.cancellation_reason', 'RUC del cliente incorrecto');

        $this->postJson($cancel, ['reason' => 'Otra vez'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVOICE_NOT_CANCELLABLE');

        $this->getJson("/api/work-orders/{$workOrder['id']}")->assertJsonPath('data.invoice', null);

        // Se puede refacturar la OT; el número anulado no se reutiliza.
        $new = $this->generateInvoice($workOrder);
        $this->patchJson("/api/invoices/{$new['id']}", ['customer_tax_id' => '80012345-7'])->assertOk();

        $this->postJson("/api/invoices/{$new['id']}/issue")
            ->assertOk()
            ->assertJsonPath('data.number', '001-001-0000002');
    }

    public function test_stamp_rules(): void
    {
        $this->actingAsAdmin();

        $stamp = $this->createStamp()->assertCreated()->json('data');

        // Solo uno activo por establecimiento y punto.
        $this->createStamp(['stamp_number' => '87654321'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'INVOICE_STAMP_OVERLAP');

        $this->createStamp(['stamp_number' => '1234'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['stamp_number']);

        $this->createStamp(['valid_until' => now()->subYears(2)->toDateString()])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['valid_until']);

        // Otro punto de expedición: dos vigentes → hay que indicar cuál.
        $other = $this->createStamp(['stamp_number' => '87654321', 'expedition_point' => '002'])
            ->assertCreated()
            ->json('data');

        $invoice = $this->readyInvoice();

        $this->postJson("/api/invoices/{$invoice['id']}/issue")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVOICE_STAMP_REQUIRED');

        $this->postJson("/api/invoices/{$invoice['id']}/issue", ['invoice_stamp_id' => $other['id']])
            ->assertOk()
            ->assertJsonPath('data.number', '001-002-0000001');

        // Desactivado: ya no se puede usar.
        $this->patchJson("/api/invoice-stamps/{$stamp['id']}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $second = $this->readyInvoice();

        $this->postJson("/api/invoices/{$second['id']}/issue", ['invoice_stamp_id' => $stamp['id']])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVOICE_STAMP_NOT_AVAILABLE');
    }

    public function test_expired_and_exhausted_stamps(): void
    {
        $this->actingAsAdmin();

        $expired = $this->createStamp([
            'valid_from' => now()->subYears(2)->toDateString(),
            'valid_until' => now()->subDay()->toDateString(),
        ])->assertCreated()->json('data');

        $invoice = $this->readyInvoice();

        $this->postJson("/api/invoices/{$invoice['id']}/issue")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVOICE_STAMP_NOT_AVAILABLE');

        $this->patchJson("/api/invoice-stamps/{$expired['id']}", ['is_active' => false])->assertOk();

        $this->createStamp(['stamp_number' => '87654321', 'next_number' => 9999999])->assertCreated();

        $this->postJson("/api/invoices/{$invoice['id']}/issue")
            ->assertOk()
            ->assertJsonPath('data.number', '001-001-9999999');

        $next = $this->readyInvoice();

        $this->postJson("/api/invoices/{$next['id']}/issue")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVOICE_STAMP_EXHAUSTED');
    }
}
