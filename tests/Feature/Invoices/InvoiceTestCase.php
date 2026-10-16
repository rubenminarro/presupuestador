<?php

namespace Tests\Feature\Invoices;

use Illuminate\Testing\TestResponse;
use Tests\Feature\WorkOrderParts\WorkOrderPartsTestCase;

abstract class InvoiceTestCase extends WorkOrderPartsTestCase
{
    /**
     * OT completada. Venta sin IVA del cierre:
     *   mano de obra 200.000 + tornillos (fuera de catálogo) 5.000
     *   + aceite usado 1,7 L × 150 = 255  → 205.255.
     */
    protected function completedWorkOrder(): array
    {
        $oil = $this->createPartViaApi(null, ['unit' => 'liter']);
        $this->entry($oil['id'], 10, 100, ['type' => 'initial'])->assertCreated();

        $budgetId = $this->createBudgetWithItems([
            ['type' => 'part', 'part_id' => $oil['id'], 'description' => 'Aceite', 'quantity' => 2, 'unit_price' => 150],
            ['type' => 'labor', 'description' => 'Cambio de embrague', 'quantity' => 1, 'unit_price' => 200000],
            ['type' => 'part', 'description' => 'Tornillos', 'quantity' => 1, 'unit_price' => 5000],
        ]);

        $this->approveBudget($budgetId);

        $workOrder = $this->postJson('/api/work-orders', [
            'budget_id' => $budgetId,
            'mechanic_id' => $this->createMechanic(['commission_percentage' => 10])->id,
        ])->assertCreated()->json('data');

        $this->startWorkOrder($workOrder);

        $this->postJson($this->partsUrl($workOrder, $workOrder['parts'][0]['id'], 'consume'), ['quantity_used' => 1.7])
            ->assertOk();

        $this->completeAllItems($workOrder);
        $this->postJson("/api/work-orders/{$workOrder['id']}/complete")->assertOk();

        return $workOrder;
    }

    protected function generateInvoice(array $workOrder): array
    {
        return $this->postJson("/api/work-orders/{$workOrder['id']}/invoice")->assertCreated()->json('data');
    }

    protected function createStamp(array $overrides = []): TestResponse
    {
        return $this->postJson('/api/invoice-stamps', [
            'stamp_number' => '12345678',
            'establishment' => '001',
            'expedition_point' => '001',
            'valid_from' => now()->subMonth()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            ...$overrides,
        ]);
    }

    /**
     * Borrador listo para emitir (con RUC del cliente).
     */
    protected function readyInvoice(): array
    {
        $invoice = $this->generateInvoice($this->completedWorkOrder());

        $this->patchJson("/api/invoices/{$invoice['id']}", ['customer_tax_id' => '80012345-6'])->assertOk();

        return $invoice;
    }

    protected function item(array $invoice, string $description): array
    {
        return collect($invoice['items'])->firstWhere('description', $description);
    }
}
