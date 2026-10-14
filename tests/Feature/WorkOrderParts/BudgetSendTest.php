<?php

namespace Tests\Feature\WorkOrderParts;

use App\Models\Budget;

class BudgetSendTest extends WorkOrderPartsTestCase
{
    public function test_budget_without_items_cannot_be_sent(): void
    {
        $this->actingAsAdmin();

        $budgetId = $this->createBudgetWithItems([]);

        $this->postJson("/api/budgets/{$budgetId}/send")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'BUDGET_WITHOUT_ITEMS');

        $this->assertSame('draft', Budget::find($budgetId)->status->value);
    }

    public function test_budget_with_items_can_be_sent(): void
    {
        $this->actingAsAdmin();

        $budgetId = $this->createBudgetWithItems([
            ['type' => 'labor', 'description' => 'Mano de obra', 'quantity' => 1, 'unit_price' => 500],
        ]);

        $this->postJson("/api/budgets/{$budgetId}/send")->assertOk();

        $this->assertSame('sent', Budget::find($budgetId)->status->value);
    }

    public function test_enum_validation_messages_are_in_spanish(): void
    {
        $this->actingAsAdmin();

        $budgetId = $this->createBudgetWithItems([]);

        $this->postJson("/api/budgets/{$budgetId}/items", [
            'type' => 'otro',
            'description' => 'Item',
            'quantity' => 1,
            'unit_price' => 100,
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.type.0', 'El tipo de ítem debe ser un valor válido.');
    }
}
