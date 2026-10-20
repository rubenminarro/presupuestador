<?php

namespace Tests\Feature\Reports;

use Illuminate\Support\Facades\Mail;
use Tests\Feature\Invoices\InvoiceTestCase;

class ReportExportTest extends InvoiceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_sales_report_exports_to_csv(): void
    {
        $this->actingAsAdmin();

        $invoice = $this->readyInvoice();
        $this->createStamp()->assertCreated();
        $this->postJson("/api/invoices/{$invoice['id']}/issue")->assertOk();

        $from = now()->startOfMonth()->toDateString();
        $to = now()->endOfMonth()->toDateString();

        $response = $this->get('/api/reports/sales?format=csv')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', "attachment; filename=\"reporte-ventas_{$from}_{$to}.csv\"");

        $csv = $response->getContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("\"Reporte de ventas\"", $csv);
        $this->assertStringContainsString("Período;\"{$from} a {$to}\"", $csv);
        $this->assertStringContainsString("Concepto;Valor", $csv);
        $this->assertStringContainsString("Facturas;1", $csv);
        $this->assertStringContainsString("\"Gravadas 10%\";225781,00", $csv);
        $this->assertStringContainsString('"Cobrado — Por medio de pago"', $csv);
    }

    public function test_every_report_exports_to_pdf_and_keeps_json_by_default(): void
    {
        $this->actingAsAdmin();

        foreach (['sales', 'budgets', 'profitability', 'productivity', 'inventory', 'clients'] as $report) {
            $response = $this->get("/api/reports/{$report}?format=pdf")
                ->assertOk()
                ->assertHeader('Content-Type', 'application/pdf');

            $this->assertStringStartsWith('%PDF', $response->getContent());

            $this->get("/api/reports/{$report}?format=csv")->assertOk();
        }

        $this->getJson('/api/reports/sales')
            ->assertOk()
            ->assertJsonPath('message', 'Reporte de ventas obtenido correctamente.')
            ->assertJsonStructure(['data' => ['period', 'totals']]);

        $this->getJson('/api/reports/sales?format=xlsx')->assertStatus(422)->assertJsonValidationErrors(['format']);
    }

    public function test_export_keeps_report_permissions(): void
    {
        $this->actingAsRole('cajero');

        $this->get('/api/reports/sales?format=csv')->assertOk();
        $this->getJson('/api/reports/profitability?format=pdf')->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
    }
}
