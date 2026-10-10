<?php

namespace Tests\Feature\WorkOrderParts;

use App\Models\InventoryMovement;
use App\Models\WorkOrderPart;

class WorkOrderPartFlowTest extends WorkOrderPartsTestCase
{
    public function test_full_flow_from_budget_to_completed_work_order(): void
    {
        $this->actingAsAdmin();

        $oil = $this->createPartViaApi(null, ['unit' => 'liter']);
        $this->entry($oil['id'], 10, 100, ['type' => 'initial'])->assertCreated();

        $workOrder = $this->workOrderWithPart($oil, 2, 150);

        // La OT nace con la línea del repuesto reservada.
        $this->assertCount(1, $workOrder['parts']);
        $line = $workOrder['parts'][0];

        $this->assertSame('reserved', $line['status']['value']);
        $this->assertTrue($line['is_budgeted']);
        $this->assertSame('2.000', $line['quantity']);
        $this->assertSame('150.00', $line['unit_price']);
        $this->assertSame('2.000', $this->stockOf($oil['id'])->quantity_reserved);

        $this->startWorkOrder($workOrder);

        // Presupuestado 2 L, utilizado 1,7 L.
        $this->postJson($this->partsUrl($workOrder, $line['id'], 'consume'), ['quantity_used' => 1.7])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'consumed')
            ->assertJsonPath('data.quantity_consumed', '1.700')
            ->assertJsonPath('data.quantity_net', '1.700')
            ->assertJsonPath('data.unit_cost', '100.0000')
            ->assertJsonPath('data.total_cost', '170.00')
            ->assertJsonPath('data.total_price', '255.00');

        $stock = $this->stockOf($oil['id']);
        $this->assertSame('8.300', $stock->quantity_on_hand);
        $this->assertSame('0.000', $stock->quantity_reserved);

        $movement = InventoryMovement::where('type', 'work_order_out')->sole();
        $this->assertSame('work_order_part', $movement->reference_type);
        $this->assertSame($line['id'], $movement->reference_id);
        $this->assertSame('-1.700', $movement->quantity);

        $this->completeAllItems($workOrder);

        $this->postJson("/api/work-orders/{$workOrder['id']}/complete")
            ->assertOk()
            ->assertJsonPath('data.status.value', 'completed')
            ->assertJsonPath('data.parts_summary.total_cost', '170.00')
            ->assertJsonPath('data.parts_summary.total_price', '255.00')
            ->assertJsonPath('data.parts_summary.unresolved_count', 0);
    }

    public function test_budget_items_without_catalog_part_do_not_create_lines(): void
    {
        $this->actingAsAdmin();

        $budgetId = $this->createBudgetWithItems([
            ['type' => 'part', 'description' => 'Repuesto fuera de catalogo', 'quantity' => 1, 'unit_price' => 100],
        ]);

        $this->approveBudget($budgetId);

        $workOrder = $this->createWorkOrder($budgetId);

        $this->assertCount(1, $workOrder['items']);
        $this->assertCount(0, $workOrder['parts']);
    }

    public function test_missing_stock_leaves_line_pending_until_reserved(): void
    {
        $this->actingAsAdmin();

        $filter = $this->createPartViaApi();

        $workOrder = $this->workOrderWithPart($filter, 2);
        $line = $workOrder['parts'][0];

        $this->assertSame('pending', $line['status']['value']);
        $this->assertSame(1, $workOrder['parts_summary']['unresolved_count']);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'reserve'))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INSUFFICIENT_STOCK');

        $this->entry($filter['id'], 5, 100)->assertCreated();

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'reserve'))
            ->assertOk()
            ->assertJsonPath('data.status.value', 'reserved');

        $this->assertSame('2.000', $this->stockOf($filter['id'])->quantity_reserved);
    }

    public function test_consuming_a_pending_line_takes_stock_from_available(): void
    {
        $this->actingAsAdmin();

        $filter = $this->createPartViaApi();

        $workOrder = $this->workOrderWithPart($filter, 2);
        $line = $workOrder['parts'][0];

        $this->entry($filter['id'], 3, 100)->assertCreated();
        $this->startWorkOrder($workOrder);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'consume'), ['quantity_used' => 2])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'consumed');

        $this->assertSame('1.000', $this->stockOf($filter['id'])->quantity_on_hand);
    }

    public function test_cancelling_work_order_releases_reservations(): void
    {
        $this->actingAsAdmin();

        $filter = $this->createPartViaApi();
        $this->entry($filter['id'], 5, 100)->assertCreated();

        $workOrder = $this->workOrderWithPart($filter, 3);

        $this->assertSame('3.000', $this->stockOf($filter['id'])->quantity_reserved);

        $this->postJson("/api/work-orders/{$workOrder['id']}/cancel")
            ->assertOk()
            ->assertJsonPath('data.parts.0.status.value', 'cancelled');

        $stock = $this->stockOf($filter['id']);
        $this->assertSame('0.000', $stock->quantity_reserved);
        $this->assertSame('5.000', $stock->quantity_on_hand);
    }

    public function test_work_order_cannot_be_completed_with_unresolved_parts(): void
    {
        $this->actingAsAdmin();

        $filter = $this->createPartViaApi();
        $this->entry($filter['id'], 5, 100)->assertCreated();

        $workOrder = $this->workOrderWithPart($filter, 1);
        $line = $workOrder['parts'][0];

        $this->startWorkOrder($workOrder);
        $this->completeAllItems($workOrder);

        $this->postJson("/api/work-orders/{$workOrder['id']}/complete")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_HAS_UNRESOLVED_PARTS');

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'cancel'))
            ->assertOk()
            ->assertJsonPath('data.status.value', 'cancelled');

        $this->postJson("/api/work-orders/{$workOrder['id']}/complete")->assertOk();

        $this->assertSame('0.000', $this->stockOf($filter['id'])->quantity_reserved);

        // Con la OT completada, los repuestos quedan congelados.
        $this->postJson($this->partsUrl($workOrder), ['part_id' => $filter['id'], 'quantity' => 1])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PARTS_NOT_EDITABLE');
    }

    public function test_cancelling_a_budgeted_item_cancels_its_open_parts(): void
    {
        $this->actingAsAdmin();

        $filter = $this->createPartViaApi();
        $this->entry($filter['id'], 5, 100)->assertCreated();

        $workOrder = $this->workOrderWithPart($filter, 2);
        $line = $workOrder['parts'][0];

        $this->startWorkOrder($workOrder);

        $this->postJson("/api/work-orders/{$workOrder['id']}/items/{$line['work_order_item_id']}/cancel")
            ->assertOk();

        $this->assertSame('cancelled', WorkOrderPart::find($line['id'])->status->value);
        $this->assertSame('0.000', $this->stockOf($filter['id'])->quantity_reserved);
    }

    public function test_partial_and_total_returns(): void
    {
        $this->actingAsAdmin();

        $pads = $this->createPartViaApi();
        $this->entry($pads['id'], 10, 100, ['type' => 'initial'])->assertCreated();

        $workOrder = $this->workOrderWithPart($pads, 3);
        $line = $workOrder['parts'][0];

        $this->startWorkOrder($workOrder);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'consume'), ['quantity_used' => 3])->assertOk();

        // Una compra posterior cambia el promedio; la devolución se valúa al costo de la línea.
        $this->entry($pads['id'], 7, 200)->assertCreated();

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'return'), ['quantity' => 1, 'reason' => 'Sobrante'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'consumed')
            ->assertJsonPath('data.quantity_returned', '1.000')
            ->assertJsonPath('data.quantity_net', '2.000')
            ->assertJsonPath('data.total_cost', '200.00');

        $return = InventoryMovement::where('type', 'work_order_return')->sole();
        $this->assertSame('100.0000', $return->unit_cost);
        $this->assertSame('1.000', $return->quantity);

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'return'), ['quantity' => 3, 'reason' => 'Sobrante'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_PART_RETURN_EXCEEDS_CONSUMED');

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'return'), ['quantity' => 2, 'reason' => 'No se instalo'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'returned')
            ->assertJsonPath('data.quantity_net', '0.000');

        $this->assertSame('17.000', $this->stockOf($pads['id'])->quantity_on_hand);
    }

    public function test_returns_are_accepted_for_discontinued_parts(): void
    {
        $this->actingAsAdmin();

        $pads = $this->createPartViaApi();
        $this->entry($pads['id'], 2, 100)->assertCreated();

        $workOrder = $this->workOrderWithPart($pads, 2);
        $line = $workOrder['parts'][0];

        $this->startWorkOrder($workOrder);
        $this->postJson($this->partsUrl($workOrder, $line['id'], 'consume'), ['quantity_used' => 2])->assertOk();

        $this->postJson("/api/parts/{$pads['id']}/discontinue")->assertOk();

        $this->postJson($this->partsUrl($workOrder, $line['id'], 'return'), ['quantity' => 1, 'reason' => 'Sobrante'])
            ->assertOk();
    }
}
