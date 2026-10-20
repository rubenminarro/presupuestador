<?php

namespace Tests\Feature\Documents;

use App\Mail\ClientNotificationMail;
use App\Models\Budget;
use App\Models\Invoice;
use App\Models\InvoiceStamp;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Invoices\InvoiceTestCase;

class DocumentPdfTest extends InvoiceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('public');

        config(['company.legal_name' => 'Taller Ejemplo S.A.', 'company.tax_id' => '80000001-1']);
    }

    private function assertPdf(TestResponse $response, string $filename): void
    {
        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /**
     * Factura emitida y su cliente con email (para el recibo y el adjunto).
     */
    private function issuedInvoice(string $condition = 'cash'): array
    {
        $invoice = $this->readyInvoice();

        if (!InvoiceStamp::exists()) {
            $this->createStamp()->assertCreated();
        }

        if ($condition === 'credit') {
            $this->patchJson("/api/invoices/{$invoice['id']}", [
                'condition' => 'credit',
                'due_date' => now()->addDays(30)->toDateString(),
            ])->assertOk();
        }

        return $this->postJson("/api/invoices/{$invoice['id']}/issue")->assertOk()->json('data');
    }

    public function test_budget_pdf_and_email_attachment(): void
    {
        $this->actingAsAdmin();

        $budgetId = $this->createBudgetWithItems([
            ['type' => 'labor', 'description' => 'Cambio de embrague', 'quantity' => 1, 'unit_price' => 200000],
        ]);
        $budget = Budget::findOrFail($budgetId);

        // Borrador: se imprime con marca de agua.
        $this->assertPdf($this->get("/api/budgets/{$budgetId}/pdf"), "presupuesto-{$budget->code}.pdf");

        $budget->reception->client->update(['email' => 'ana@example.com']);
        $this->postJson("/api/budgets/{$budgetId}/send")->assertOk();

        Mail::assertSent(ClientNotificationMail::class, function (ClientNotificationMail $mail) use ($budget) {
            $attachments = $mail->attachments();

            return count($attachments) === 1
                && $attachments[0]->as === "presupuesto-{$budget->code}.pdf";
        });
    }

    public function test_invoice_pdf_for_draft_issued_and_cancelled(): void
    {
        $this->actingAsAdmin();

        $draft = $this->readyInvoice();
        $this->assertPdf($this->get("/api/invoices/{$draft['id']}/pdf"), "factura-borrador-{$draft['id']}.pdf");

        $this->createStamp()->assertCreated();
        $issued = $this->postJson("/api/invoices/{$draft['id']}/issue")->assertOk()->json('data');
        $this->assertPdf($this->get("/api/invoices/{$issued['id']}/pdf"), "factura-{$issued['number']}.pdf");

        $this->postJson("/api/invoices/{$issued['id']}/cancel", ['reason' => 'Error de carga'])->assertOk();
        $this->assertPdf($this->get("/api/invoices/{$issued['id']}/pdf"), "factura-{$issued['number']}.pdf");
    }

    public function test_receipt_only_for_credit_payments(): void
    {
        $this->actingAsAdmin();

        $credit = $this->issuedInvoice('credit');
        $payment = $this->postJson("/api/invoices/{$credit['id']}/payments", ['amount' => 1000, 'method' => 'cash'])
            ->assertCreated()
            ->json('data');

        $this->assertPdf($this->get("/api/payments/{$payment['id']}/receipt"), "recibo-{$payment['receipt_number']}.pdf");

        $cash = $this->issuedInvoice();
        $cashPayment = $this->postJson("/api/invoices/{$cash['id']}/payments", ['amount' => 1000, 'method' => 'cash'])
            ->assertCreated()
            ->json('data');

        $this->getJson("/api/payments/{$cashPayment['id']}/receipt")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PAYMENT_WITHOUT_RECEIPT');
    }

    public function test_delivery_pdf_with_signature(): void
    {
        $this->actingAsAdmin();

        $invoice = $this->issuedInvoice();
        $this->postJson("/api/invoices/{$invoice['id']}/payments", [
            'amount' => (int) Invoice::findOrFail($invoice['id'])->total,
            'method' => 'transfer',
            'reference' => 'TR-1',
            'bank' => 'Itaú',
        ])->assertCreated();

        $receptionId = Invoice::findOrFail($invoice['id'])->workOrder->reception_id;

        $delivery = $this->post("/api/receptions/{$receptionId}/delivery", [
            'final_mileage' => 100000,
            'received_by_name' => 'Ana Gomez',
            'received_by_document' => '1234567',
            'customer_accepted' => true,
            'signature' => UploadedFile::fake()->image('firma.png', 200, 80),
        ], ['Accept' => 'application/json'])->assertCreated()->json('data');

        $this->assertPdf(
            $this->get("/api/vehicle-deliveries/{$delivery['id']}/pdf"),
            "entrega-{$delivery['delivery_number']}.pdf"
        );
    }

    public function test_documents_use_show_permissions(): void
    {
        $this->actingAsAdmin();

        $invoice = $this->readyInvoice();
        $budgetId = Invoice::findOrFail($invoice['id'])->workOrder->budget_id;

        $this->actingAsRole('cajero');
        $this->get("/api/invoices/{$invoice['id']}/pdf")->assertOk();

        $this->actingAsMechanic();
        $this->getJson("/api/invoices/{$invoice['id']}/pdf")->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
        $this->getJson("/api/budgets/{$budgetId}/pdf")->assertForbidden();

        $this->actingAsAdmin();
        $this->getJson('/api/invoices/999999/pdf')->assertNotFound()->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');
    }
}
