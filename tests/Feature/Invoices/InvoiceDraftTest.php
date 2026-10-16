<?php

namespace Tests\Feature\Invoices;

class InvoiceDraftTest extends InvoiceTestCase
{
    public function test_draft_is_generated_from_what_was_actually_used(): void
    {
        $this->actingAsAdmin();

        $workOrder = $this->completedWorkOrder();
        $invoice = $this->generateInvoice($workOrder);

        $this->assertSame('draft', $invoice['status']['value']);
        $this->assertNull($invoice['number']);
        $this->assertSame('Ana Gomez', $invoice['customer']['name']);
        $this->assertSame('cash', $invoice['condition']['value']);
        $this->assertCount(3, $invoice['items']);

        // IVA incluido al 10%: precio sin IVA × 1,10, redondeado a guaraníes.
        $labor = $this->item($invoice, 'Cambio de embrague');
        $this->assertSame('200000.00', $labor['base_unit_price']);
        $this->assertSame('220000.00', $labor['unit_price']);
        $this->assertSame('220000.00', $labor['total']);
        $this->assertSame('work_order_item', $labor['source']['type']);

        $screws = $this->item($invoice, 'Tornillos');
        $this->assertSame('5500.00', $screws['total']);

        // Aceite: se factura lo usado (1,7 L), no lo presupuestado (2 L). 1,7 × 165 = 280,5 → 281.
        $oil = collect($invoice['items'])->firstWhere('source.type', 'work_order_part');
        $this->assertSame('1.700', $oil['quantity']);
        $this->assertSame('165.00', $oil['unit_price']);
        $this->assertSame('281.00', $oil['total']);

        // Liquidación: 225.781 ÷ 11 = 20.525,5 → 20.526.
        $this->assertSame('225781.00', $invoice['totals']['taxed_10']);
        $this->assertSame('20526.00', $invoice['totals']['iva_10']);
        $this->assertSame('0.00', $invoice['totals']['exempt']);
        $this->assertSame('225781.00', $invoice['totals']['total']);

        // El detalle de la OT muestra su factura vigente.
        $this->getJson("/api/work-orders/{$workOrder['id']}")
            ->assertJsonPath('data.invoice.id', $invoice['id'])
            ->assertJsonPath('data.invoice.status.value', 'draft');
    }

    public function test_tax_rates_and_discounts_recalculate_the_draft(): void
    {
        $this->actingAsAdmin();

        $invoice = $this->generateInvoice($this->completedWorkOrder());
        $url = "/api/invoices/{$invoice['id']}/items";

        $screws = $this->item($invoice, 'Tornillos');
        $oil = collect($invoice['items'])->firstWhere('source.type', 'work_order_part');
        $labor = $this->item($invoice, 'Cambio de embrague');

        // Exento: el precio con IVA vuelve al precio sin IVA.
        $this->patchJson("{$url}/{$screws['id']}", ['tax_rate' => 0])
            ->assertOk()
            ->assertJsonPath('data.totals.exempt', '5000.00');

        // 5%: 150 × 1,05 = 157,5 → 158; 1,7 × 158 = 268,6 → 269; IVA 269 ÷ 21 = 12,8 → 13.
        $this->patchJson("{$url}/{$oil['id']}", ['tax_rate' => 5])
            ->assertOk()
            ->assertJsonPath('data.totals.taxed_5', '269.00')
            ->assertJsonPath('data.totals.iva_5', '13.00');

        // Descuento con IVA incluido: 220.000 − 20.000 = 200.000; IVA 200.000 ÷ 11 = 18.181,8 → 18.182.
        $response = $this->patchJson("{$url}/{$labor['id']}", ['discount' => 20000, 'description' => 'Cambio de embrague completo'])
            ->assertOk()
            ->assertJsonPath('data.totals.taxed_10', '200000.00')
            ->assertJsonPath('data.totals.iva_10', '18182.00')
            ->assertJsonPath('data.totals.discount', '20000.00')
            ->assertJsonPath('data.totals.total', '205269.00');

        $this->assertNotNull($this->item($response->json('data'), 'Cambio de embrague completo'));

        $this->patchJson("{$url}/{$labor['id']}", ['discount' => 300000])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVOICE_DISCOUNT_EXCEEDS_AMOUNT');

        $this->patchJson("{$url}/{$labor['id']}", ['tax_rate' => 7])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tax_rate']);
    }

    public function test_customer_data_and_condition_can_be_edited_in_draft(): void
    {
        $this->actingAsAdmin();

        $invoice = $this->generateInvoice($this->completedWorkOrder());
        $url = "/api/invoices/{$invoice['id']}";

        $this->patchJson($url, [
            'customer_name' => 'Transportes del Sur S.A.',
            'customer_tax_id' => '80012345-6',
            'customer_address' => 'Av. España 1234',
            'condition' => 'credit',
            'due_date' => now()->addDays(30)->toDateString(),
        ])
            ->assertOk()
            ->assertJsonPath('data.customer.name', 'Transportes del Sur S.A.')
            ->assertJsonPath('data.condition.value', 'credit')
            ->assertJsonPath('data.due_date', now()->addDays(30)->toDateString());

        // Al volver a contado se descarta el vencimiento.
        $this->patchJson($url, ['condition' => 'cash'])
            ->assertOk()
            ->assertJsonPath('data.due_date', null);

        $this->patchJson($url, ['condition' => 'otro', 'customer_tax_id' => 'RUC 800!'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['condition', 'customer_tax_id']);
    }

    public function test_work_order_must_be_completed_and_not_already_invoiced(): void
    {
        $this->actingAsAdmin();

        $budgetId = $this->createBudgetWithItems([
            ['type' => 'labor', 'description' => 'Trabajo', 'quantity' => 1, 'unit_price' => 1000],
        ]);
        $this->approveBudget($budgetId);
        $pending = $this->createWorkOrder($budgetId);

        $this->postJson("/api/work-orders/{$pending['id']}/invoice")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_NOT_INVOICEABLE');

        $workOrder = $this->completedWorkOrder();
        $invoice = $this->generateInvoice($workOrder);

        $this->postJson("/api/work-orders/{$workOrder['id']}/invoice")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'WORK_ORDER_ALREADY_INVOICED');

        // Descartar el borrador libera la OT y no consume número.
        $this->deleteJson("/api/invoices/{$invoice['id']}")->assertOk();
        $this->assertDatabaseMissing('invoice_items', ['invoice_id' => $invoice['id']]);

        $this->generateInvoice($workOrder);
    }

    public function test_listing_and_filters(): void
    {
        $this->actingAsAdmin();

        $draft = $this->generateInvoice($this->completedWorkOrder());

        $this->getJson('/api/invoices?status=draft')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $draft['id'])
            ->assertJsonPath('meta.pagination.total', 1);

        $this->getJson('/api/invoices?status=issued')->assertOk()->assertJsonCount(0, 'data');

        $this->getJson('/api/invoices?search=Ana')->assertOk()->assertJsonCount(1, 'data');

        $this->getJson('/api/invoices?status=pagada')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR');

        $this->getJson("/api/invoices/{$draft['id']}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['items', 'totals', 'customer', 'stamp']]);
    }

    public function test_mechanic_has_no_access_to_invoicing(): void
    {
        $this->actingAsAdmin();

        $workOrder = $this->completedWorkOrder();
        $invoice = $this->generateInvoice($workOrder);

        $this->actingAsMechanic();

        $this->getJson('/api/invoices')->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
        $this->getJson("/api/invoices/{$invoice['id']}")->assertForbidden();
        $this->postJson("/api/work-orders/{$workOrder['id']}/invoice")->assertForbidden();
        $this->postJson("/api/invoices/{$invoice['id']}/issue")->assertForbidden();
        $this->getJson('/api/invoice-stamps')->assertForbidden();

        // El detalle de la OT no le muestra la factura.
        $this->getJson("/api/work-orders/{$workOrder['id']}")
            ->assertOk()
            ->assertJsonMissingPath('data.invoice');
    }
}
