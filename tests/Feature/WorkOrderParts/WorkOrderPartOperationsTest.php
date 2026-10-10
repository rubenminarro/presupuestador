<?php

namespace Tests\Feature\WorkOrderParts;

class WorkOrderPartOperationsTest extends WorkOrderPartsTestCase
{
    public function test_extra_part_is_added_with_catalog_price(): void
    {
        $this->actingAsAdmin();

        $budgeted = $this->createPartViaApi();
        $extra = $this->createPartViaApi(null, ['sale_price' => 72000]);
        $this->entry($extra['id'], 4, 100)->assertCreated();

        $workOrder = $this->workOrderWithPart($budgeted, 1);

        $this->postJson($this->partsUrl($workOrder), ['part_id' => $extra['id'], 'quantity' => 2])
            ->assertCreated()
            ->assertJsonPath('data.work_order_item_id', null)
            ->assertJsonPath('data.is_budgeted', false)
            ->assertJsonPath('data.unit_price', '72000.00')
            ->assertJsonPath('data.status.value', 'reserved');

        $this->postJson($this->partsUrl($workOrder), ['part_id' => $extra['id'], 'quantity' => 1, 'unit_price' => 65000])
            ->assertCreated()
            ->assertJsonPath('data.unit_price', '65000.00');

        $this->getJson($this->partsUrl($workOrder))
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_changing_quantity_adjusts_the_reservation(): void
    {
        $this->actingAsAdmin();

        $filter = $this->createPartViaApi();
        $this->entry($filter['id'], 10, 100)->assertCreated();

        $workOrder = $this->workOrderWithPart($filter, 2);
        $line = $workOrder['parts'][0];

        $this->patchJson($this->partsUrl($workOrder, $line['id']), ['quantity' => 5])
            ->assertOk()
            ->assertJsonPath('data.quantity', '5.000');
        $this->assertSame('5.000', $this->stockOf($filter['id'])->quantity_reserved);

        $this->patchJson($this->partsUrl($workOrder, $line['id']), ['quantity' => 1, 'notes' => 'Ajuste'])
            ->assertOk()
            ->assertJsonPath('data.notes', 'Ajuste');
        $this->assertSame('1.000', $this->stockOf($filter['id'])->quantity_reserved);

        $this->patchJson($this->partsUrl($workOrder, $line['id']), ['quantity' => 50])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INSUFFICIENT_STOCK');
        $this->assertSame('1.000', $this->stockOf($filter['id'])->quantity_reserved);

        $this->patchJson($this->partsUrl($workOrder, $line['id']), ['quantity' => 1.5])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVENTORY_FRACTIONAL_QUANTITY_NOT_ALLOWED');
    }

    public function test_status_rules_for_lines(): void
    {
        $this->actingAsAdmin();

        $filter = $this->createPartViaApi();
        $this->entry($filter['id'], 10, 100)->assertCreated();

        $workOrder = $this->workOrderWithPart($filter, 2);
        $line = $workOrder['parts'][0];

        // Consumir requiere la OT en progreso.
        $this->postJson($this->partsUrl($workOrder, $line['id'], 'consume'), ['quantity_used' => 2])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_NOT_IN_PROGRESS');

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'reserve'))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PART_INVALID_STATUS_TRANSITION');

        $this->startWorkOrder($workOrder);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'return'), ['quantity' => 1, 'reason' => 'Prueba'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PART_INVALID_STATUS_TRANSITION');

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'consume'), ['quantity_used' => 2])->assertOk();

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'consume'), ['quantity_used' => 1])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PART_INVALID_STATUS_TRANSITION');

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'cancel'))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PART_INVALID_STATUS_TRANSITION');

        $this->patchJson($this->partsUrl($workOrder, $line['id']), ['quantity' => 3])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PART_INVALID_STATUS_TRANSITION');
    }

    public function test_consumption_cannot_exceed_reserved_plus_available(): void
    {
        $this->actingAsAdmin();

        $oil = $this->createPartViaApi(null, ['unit' => 'liter']);
        $this->entry($oil['id'], 3, 100)->assertCreated();

        $workOrder = $this->workOrderWithPart($oil, 2);
        $line = $workOrder['parts'][0];

        $this->startWorkOrder($workOrder);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'consume'), ['quantity_used' => 3.5])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INSUFFICIENT_STOCK');

        // El rechazo no altera la reserva ni el stock.
        $stock = $this->stockOf($oil['id']);
        $this->assertSame('2.000', $stock->quantity_reserved);
        $this->assertSame('3.000', $stock->quantity_on_hand);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'consume'), ['quantity_used' => 2.5])
            ->assertOk()
            ->assertJsonPath('data.quantity_consumed', '2.500');

        $stock = $this->stockOf($oil['id']);
        $this->assertSame('0.000', $stock->quantity_reserved);
        $this->assertSame('0.500', $stock->quantity_on_hand);
    }

    public function test_inactive_parts_cannot_be_budgeted_or_added(): void
    {
        $this->actingAsAdmin();

        $inactive = $this->createPartViaApi();
        $this->postJson("/api/parts/{$inactive['id']}/deactivate")->assertOk();

        $budgetId = $this->createBudgetWithItems([]);

        $this->postJson("/api/budgets/{$budgetId}/items", [
            'type' => 'part',
            'part_id' => $inactive['id'],
            'description' => 'Repuesto inactivo',
            'quantity' => 1,
            'unit_price' => 100,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PART_NOT_USABLE');

        $workOrder = $this->workOrderWithPart($this->createPartViaApi(), 1);

        $this->postJson($this->partsUrl($workOrder), ['part_id' => $inactive['id'], 'quantity' => 1])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PART_NOT_USABLE');
    }

    public function test_budget_item_part_rules(): void
    {
        $this->actingAsAdmin();

        $filter = $this->createPartViaApi();
        $oil = $this->createPartViaApi(null, ['unit' => 'liter']);

        $budgetId = $this->createBudgetWithItems([]);
        $url = "/api/budgets/{$budgetId}/items";

        $this->postJson($url, [
            'type' => 'labor',
            'part_id' => $filter['id'],
            'description' => 'Mano de obra',
            'quantity' => 1,
            'unit_price' => 100,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['part_id']);

        $this->postJson($url, [
            'type' => 'part',
            'part_id' => $filter['id'],
            'description' => 'Filtro',
            'quantity' => 1.5,
            'unit_price' => 100,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVENTORY_FRACTIONAL_QUANTITY_NOT_ALLOWED');

        $item = $this->postJson($url, [
            'type' => 'part',
            'part_id' => $oil['id'],
            'description' => 'Aceite',
            'quantity' => 1.5,
            'unit_price' => 100,
        ])
            ->assertCreated()
            ->assertJsonPath('data.part_id', $oil['id'])
            ->assertJsonPath('data.part.code', $oil['code'])
            ->json('data');

        // Al dejar de ser repuesto, el vínculo con el catálogo se elimina.
        $this->patchJson("{$url}/{$item['id']}", ['type' => 'service'])
            ->assertOk()
            ->assertJsonPath('data.part_id', null);
    }

    public function test_line_of_another_work_order_is_not_found(): void
    {
        $this->actingAsAdmin();

        $filter = $this->createPartViaApi();
        $this->entry($filter['id'], 10, 100)->assertCreated();

        $first = $this->workOrderWithPart($filter, 1);
        $second = $this->workOrderWithPart($filter, 1);

        $this->postJson($this->partsUrl($second, $first['parts'][0]['id'], 'cancel'))
            ->assertNotFound()
            ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');
    }

    public function test_mechanic_can_view_consume_and_return_only(): void
    {
        $this->actingAsAdmin();

        $filter = $this->createPartViaApi();
        $this->entry($filter['id'], 10, 100)->assertCreated();

        $workOrder = $this->workOrderWithPart($filter, 2);
        $line = $workOrder['parts'][0];
        $this->startWorkOrder($workOrder);

        $this->actingAsMechanic();

        $this->getJson($this->partsUrl($workOrder))->assertOk();

        $this->postJson($this->partsUrl($workOrder), ['part_id' => $filter['id'], 'quantity' => 1])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');
        $this->patchJson($this->partsUrl($workOrder, $line['id']), ['quantity' => 3])->assertForbidden();
        $this->postJson($this->partsUrl($workOrder, $line['id'], 'reserve'))->assertForbidden();
        $this->postJson($this->partsUrl($workOrder, $line['id'], 'cancel'))->assertForbidden();

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'consume'), ['quantity_used' => 2])->assertOk();
        $this->postJson($this->partsUrl($workOrder, $line['id'], 'return'), ['quantity' => 1, 'reason' => 'Sobrante'])
            ->assertOk();
    }
}
