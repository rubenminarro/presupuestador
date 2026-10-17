<?php

namespace Tests\Feature\Reports;

use Tests\Feature\Invoices\InvoiceTestCase;

class ReportTest extends InvoiceTestCase
{
    /**
     * Dos OT completadas (InvoiceTestCase: venta sin IVA 205.255; factura con IVA 225.781):
     *  - A: factura al contado, pagada.
     *  - B: factura a crédito, sin pagos (saldo por cobrar).
     * Además: un presupuesto enviado sin respuesta y uno rechazado, y un
     * repuesto con mínimo y sin stock.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
        $this->createStamp()->assertCreated();

        $a = $this->readyInvoice();
        $a = $this->postJson("/api/invoices/{$a['id']}/issue")->assertOk()->json('data');
        $this->postJson("/api/invoices/{$a['id']}/payments", ['amount' => 225781, 'method' => 'cash'])->assertCreated();

        $b = $this->readyInvoice();
        $this->patchJson("/api/invoices/{$b['id']}", ['condition' => 'credit', 'due_date' => now()->addDays(30)->toDateString()])->assertOk();
        $this->postJson("/api/invoices/{$b['id']}/issue")->assertOk();

        $sent = $this->createBudgetWithItems([['type' => 'labor', 'description' => 'Trabajo', 'quantity' => 1, 'unit_price' => 1000]]);
        $this->postJson("/api/budgets/{$sent}/send")->assertOk();

        $rejected = $this->createBudgetWithItems([['type' => 'labor', 'description' => 'Trabajo', 'quantity' => 1, 'unit_price' => 1000]]);
        $this->postJson("/api/budgets/{$rejected}/send")->assertOk();
        $this->postJson("/api/budgets/{$rejected}/reject")->assertOk();

        $this->createPartViaApi(null, ['minimum_stock' => 5]);
    }

    public function test_sales_report(): void
    {
        $data = $this->getJson('/api/reports/sales')->assertOk()->json('data');

        $this->assertSame(now()->startOfMonth()->toDateString(), $data['period']['date_from']);
        $this->assertSame(2, $data['totals']['invoices_count']);
        $this->assertSame('451562.00', $data['totals']['total']);
        $this->assertSame('451562.00', $data['totals']['taxed_10']);
        $this->assertSame('225781.00', $data['totals']['average_ticket']);
        $this->assertSame([['period' => now()->toDateString(), 'invoices_count' => 2, 'total' => '451562.00']], $data['by_period']);
        $this->assertCount(2, $data['by_condition']);
        $this->assertSame('225781.00', $data['collected']['total']);
        $this->assertSame(0, $data['cancelled']['invoices_count']);

        $this->getJson('/api/reports/sales?group_by=month')
            ->assertOk()
            ->assertJsonPath('data.by_period.0.period', now()->format('Y-m'));

        $this->getJson('/api/reports/sales?date_from=2020-01-01&date_to=2020-01-31')
            ->assertOk()
            ->assertJsonPath('data.totals.invoices_count', 0)
            ->assertJsonPath('data.totals.total', '0.00')
            ->assertJsonPath('data.totals.average_ticket', '0.00');

        $this->getJson('/api/reports/sales?group_by=week')->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_ERROR');
        $this->getJson('/api/reports/sales?date_from=2026-02-01&date_to=2026-01-01')->assertStatus(422);
    }

    public function test_budgets_report(): void
    {
        $data = $this->getJson('/api/reports/budgets')->assertOk()->json('data');
        $byStatus = collect($data['by_status'])->keyBy('status');

        $this->assertSame(4, $data['budgets_count']);
        $this->assertSame(2, $byStatus['approved']['budgets_count']);
        $this->assertSame(1, $byStatus['sent']['budgets_count']);
        $this->assertSame(1, $byStatus['rejected']['budgets_count']);
        // 2 aprobados / (2 aprobados + 1 rechazado).
        $this->assertSame('66.67', $data['approval_rate']);
    }

    public function test_profitability_report(): void
    {
        $totals = $this->getJson('/api/reports/profitability')->assertOk()->json('data.totals');

        // Por OT: venta 205.255; costo = aceite 170 + comisión 20.000; margen 185.085.
        $this->assertSame(2, $totals['work_orders_count']);
        $this->assertSame('410510.00', $totals['sale_total']);
        $this->assertSame('40340.00', $totals['cost_total']);
        $this->assertSame('370170.00', $totals['gross_margin']);
        $this->assertSame('90.17', $totals['margin_percentage']);
        $this->assertSame(2, $totals['incomplete_cost_count']);
    }

    public function test_productivity_report(): void
    {
        $mechanics = $this->getJson('/api/reports/productivity')->assertOk()->json('data.mechanics');

        $this->assertCount(2, $mechanics);

        foreach ($mechanics as $mechanic) {
            $this->assertSame(1, $mechanic['work_orders_as_responsible']);
            $this->assertSame(1, $mechanic['jobs_completed']);
            $this->assertSame('200000.00', $mechanic['labor_sale_attributed']);
            $this->assertSame('20000.00', $mechanic['commission_total']);
            $this->assertSame('0.00', $mechanic['hours_logged']);
        }
    }

    public function test_inventory_report(): void
    {
        $data = $this->getJson('/api/reports/inventory')->assertOk()->json('data');

        // Dos aceites: 10 L a 100 menos 1,7 L consumidos cada uno.
        $this->assertSame('1660.00', $data['stock_value']);
        $this->assertCount(1, $data['below_minimum']);
        $this->assertSame('0.000', $data['below_minimum'][0]['quantity_available']);
        $this->assertCount(2, $data['top_consumed']);
        $this->assertSame('1.700', $data['top_consumed'][0]['quantity']);
        $this->assertSame('170.00', $data['top_consumed'][0]['cost']);
        $this->assertNotEmpty(collect($data['movements_by_type'])->firstWhere('type', 'work_order_out'));
    }

    public function test_clients_report(): void
    {
        $data = $this->getJson('/api/reports/clients')->assertOk()->json('data');

        $this->assertCount(2, $data['top_clients']);
        $this->assertSame('225781.00', $data['top_clients'][0]['total']);
        $this->assertSame('225781.00', $data['receivables']['total']);
        $this->assertCount(1, $data['receivables']['clients']);
        $this->assertSame(now()->addDays(30)->toDateString(), $data['receivables']['clients'][0]['oldest_due_date']);
        $this->assertGreaterThanOrEqual(4, $data['new_clients']);
        $this->assertCount(4, $data['top_vehicles']);
    }

    public function test_dashboard_shows_only_allowed_blocks(): void
    {
        $data = $this->getJson('/api/dashboard')->assertOk()->json('data');

        $this->assertSame(['workshop', 'sales', 'budgets', 'profitability', 'productivity', 'inventory', 'maintenance'], array_keys($data));
        $this->assertSame(4, $data['workshop']['vehicles_in_shop']);
        $this->assertSame(2, $data['workshop']['completed_this_month']);
        $this->assertSame('451562.00', $data['sales']['invoiced_this_month']);
        $this->assertSame('225781.00', $data['sales']['collected_this_month']);
        $this->assertSame('225781.00', $data['sales']['receivables']);
        $this->assertSame(1, $data['budgets']['awaiting_response']);
        $this->assertSame(1, $data['inventory']['below_minimum']);

        $this->actingAsMechanic();
        $this->assertSame(['workshop'], array_keys($this->getJson('/api/dashboard')->assertOk()->json('data')));

        $this->actingAsRole('cajero');
        $this->assertSame(['workshop', 'sales'], array_keys($this->getJson('/api/dashboard')->assertOk()->json('data')));

        $this->actingAsRole('recepcionista');
        $this->assertSame(['workshop', 'budgets', 'maintenance'], array_keys($this->getJson('/api/dashboard')->assertOk()->json('data')));
    }

    public function test_report_permissions_by_area(): void
    {
        $access = [
            'cajero' => ['sales' => 200, 'clients' => 200, 'budgets' => 403, 'profitability' => 403],
            'recepcionista' => ['budgets' => 200, 'sales' => 403, 'inventory' => 403],
            'jefe-taller' => ['productivity' => 200, 'profitability' => 403, 'sales' => 403],
            'deposito' => ['inventory' => 200, 'clients' => 403, 'productivity' => 403],
            'mechanic' => ['sales' => 403, 'productivity' => 403],
        ];

        foreach ($access as $role => $reports) {
            $this->actingAsRole($role);

            foreach ($reports as $report => $status) {
                $this->getJson("/api/reports/{$report}")->assertStatus($status);
            }
        }
    }
}
