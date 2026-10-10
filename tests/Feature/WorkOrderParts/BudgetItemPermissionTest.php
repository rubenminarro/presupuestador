<?php

namespace Tests\Feature\WorkOrderParts;

class BudgetItemPermissionTest extends WorkOrderPartsTestCase
{
    public function test_budget_items_require_permissions(): void
    {
        $this->actingAsAdmin();

        $budgetId = $this->createBudgetWithItems([
            ['type' => 'labor', 'description' => 'Mano de obra', 'quantity' => 1, 'unit_price' => 500],
        ]);

        $itemId = $this->getJson("/api/budgets/{$budgetId}/items")
            ->assertOk()
            ->json('data.0.id');

        $this->actingAsMechanic();

        $this->getJson("/api/budgets/{$budgetId}/items")
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');
        $this->getJson("/api/budgets/{$budgetId}/items/{$itemId}")->assertForbidden();
        $this->postJson("/api/budgets/{$budgetId}/items", [
            'type' => 'labor',
            'description' => 'Otra',
            'quantity' => 1,
            'unit_price' => 100,
        ])->assertForbidden();
        $this->patchJson("/api/budgets/{$budgetId}/items/{$itemId}", ['unit_price' => 1])->assertForbidden();
        $this->deleteJson("/api/budgets/{$budgetId}/items/{$itemId}")->assertForbidden();
    }
}
