<?php

namespace Tests\Feature\WorkOrders;

use App\Exceptions\WorkOrderException;
use App\Models\ServiceCategory;
use App\Models\WorkOrderClosing;
use Tests\Feature\WorkOrderParts\WorkOrderPartsTestCase;

class ClosingIntegrityTest extends WorkOrderPartsTestCase
{
    private function completedWorkOrder(): array
    {
        $budgetId = $this->createBudgetWithItems([
            ['type' => 'labor', 'description' => 'Cambio de embrague', 'quantity' => 1, 'unit_price' => 1000],
        ]);

        $this->approveBudget($budgetId);

        $workOrder = $this->postJson('/api/work-orders', [
            'budget_id' => $budgetId,
            'mechanic_id' => $this->createMechanic(['commission_percentage' => 10])->id,
        ])->assertCreated()->json('data');

        $this->startWorkOrder($workOrder);
        $this->completeAllItems($workOrder);
        $this->postJson("/api/work-orders/{$workOrder['id']}/complete")->assertOk();

        return $workOrder;
    }

    public function test_closing_cannot_be_modified_or_deleted(): void
    {
        $this->actingAsAdmin();

        $workOrder = $this->completedWorkOrder();
        $closing = WorkOrderClosing::where('work_order_id', $workOrder['id'])->sole();

        foreach ([fn () => $closing->update(['sale_total' => 1]), fn () => $closing->delete()] as $attempt) {
            try {
                $attempt();
                $this->fail('Se esperaba WORK_ORDER_CLOSING_IMMUTABLE.');
            } catch (WorkOrderException $e) {
                $this->assertSame('WORK_ORDER_CLOSING_IMMUTABLE', $e->getErrorCode());
            }
        }

        $this->assertSame('1000.00', WorkOrderClosing::find($closing->id)->sale_total);
    }

    public function test_service_description_and_estimated_hours_can_be_cleared(): void
    {
        $this->actingAsAdmin();

        $category = ServiceCategory::create(['code' => 'mech', 'name' => 'Mecánica']);

        $service = $this->postJson('/api/services', [
            'service_category_id' => $category->id,
            'name' => 'Alineación',
            'description' => 'Incluye 4 ruedas.',
            'standard_price' => 120000,
            'estimated_hours' => 1.5,
        ])->assertCreated()->json('data');

        $this->patchJson("/api/services/{$service['id']}", ['description' => null, 'estimated_hours' => null])
            ->assertOk()
            ->assertJsonPath('data.description', null)
            ->assertJsonPath('data.estimated_hours', null)
            ->assertJsonPath('data.standard_price', '120000.00');
    }
}
