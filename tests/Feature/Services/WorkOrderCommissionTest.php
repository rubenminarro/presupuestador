<?php

namespace Tests\Feature\Services;

use App\Models\Mechanic;
use App\Models\ServiceCategory;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\WorkOrderParts\WorkOrderPartsTestCase;

class WorkOrderCommissionTest extends WorkOrderPartsTestCase
{
    /**
     * OT con: mano de obra 200.000, servicio de catálogo 120.000 y un repuesto.
     */
    private function workOrderWithJobs(Mechanic $responsible): array
    {
        $category = ServiceCategory::create(['code' => 'mech', 'name' => 'Mecánica']);

        $service = $this->postJson('/api/services', [
            'service_category_id' => $category->id,
            'name' => 'Alineación y balanceo',
            'standard_price' => 120000,
        ])->json('data');

        $budgetId = $this->createBudgetWithItems([
            ['type' => 'labor', 'description' => 'Cambio de embrague', 'quantity' => 1, 'unit_price' => 200000],
            ['type' => 'service', 'service_id' => $service['id'], 'description' => 'Alineación', 'quantity' => 1],
            ['type' => 'part', 'description' => 'Kit de embrague', 'quantity' => 1, 'unit_price' => 5000],
        ]);

        $this->approveBudget($budgetId);

        return $this->postJson('/api/work-orders', [
            'budget_id' => $budgetId,
            'mechanic_id' => $responsible->id,
        ])->assertCreated()->json('data');
    }

    private function itemOfType(array $workOrder, string $type): array
    {
        return collect($workOrder['items'])->firstWhere('type.value', $type);
    }

    private function mechanicsUrl(array $workOrder, array $item): string
    {
        return "/api/work-orders/{$workOrder['id']}/items/{$item['id']}/mechanics";
    }

    public function test_jobs_are_assigned_to_the_responsible_mechanic(): void
    {
        $this->actingAsAdmin();

        $juan = $this->createMechanic(['commission_percentage' => 10]);
        $workOrder = $this->workOrderWithJobs($juan);

        $labor = $this->itemOfType($workOrder, 'labor');
        $service = $this->itemOfType($workOrder, 'service');
        $part = $this->itemOfType($workOrder, 'part');

        $this->assertCount(1, $labor['mechanics']);
        $this->assertSame($juan->id, $labor['mechanics'][0]['mechanic_id']);
        $this->assertSame('100.00', $labor['mechanics'][0]['share_percentage']);
        $this->assertSame('10.00', $labor['mechanics'][0]['commission_percentage']);
        $this->assertSame('20000.00', $labor['mechanics'][0]['commission_amount']);

        $this->assertSame('12000.00', $service['mechanics'][0]['commission_amount']);
        $this->assertCount(0, $part['mechanics']);

        $this->assertSame('32000.00', $workOrder['commission_summary']['total_commission']);
        $this->assertFalse($workOrder['commission_summary']['is_final']);
    }

    public function test_custom_split_between_mechanics(): void
    {
        $this->actingAsAdmin();

        $juan = $this->createMechanic(['commission_percentage' => 10]);
        $carlos = $this->createMechanic(['commission_percentage' => 8]);
        $workOrder = $this->workOrderWithJobs($juan);
        $labor = $this->itemOfType($workOrder, 'labor');

        $response = $this->putJson($this->mechanicsUrl($workOrder, $labor), [
            'mechanics' => [
                ['mechanic_id' => $juan->id, 'share_percentage' => 70],
                ['mechanic_id' => $carlos->id, 'share_percentage' => 30],
            ],
        ])->assertOk();

        $lines = collect($response->json('data'))->keyBy('mechanic_id');

        // 200.000 × 70% × 10% = 14.000 ; 200.000 × 30% × 8% = 4.800
        $this->assertSame('14000.00', $lines[$juan->id]['commission_amount']);
        $this->assertSame('4800.00', $lines[$carlos->id]['commission_amount']);

        $summary = $this->getJson("/api/work-orders/{$workOrder['id']}")->json('data.commission_summary');

        // + servicio 120.000 × 100% × 10% = 12.000
        $this->assertSame('30800.00', $summary['total_commission']);
        $this->assertCount(2, $summary['by_mechanic']);
    }

    public function test_equal_split_when_shares_are_omitted(): void
    {
        $this->actingAsAdmin();

        $a = $this->createMechanic();
        $b = $this->createMechanic();
        $c = $this->createMechanic();
        $workOrder = $this->workOrderWithJobs($a);
        $labor = $this->itemOfType($workOrder, 'labor');

        $shares = collect($this->putJson($this->mechanicsUrl($workOrder, $labor), [
            'mechanics' => [['mechanic_id' => $a->id], ['mechanic_id' => $b->id], ['mechanic_id' => $c->id]],
        ])->assertOk()->json('data'))->pluck('share_percentage', 'mechanic_id');

        $this->assertSame('33.33', $shares[$a->id]);
        $this->assertSame('33.33', $shares[$b->id]);
        $this->assertSame('33.34', $shares[$c->id]);

        $shares = collect($this->putJson($this->mechanicsUrl($workOrder, $labor), [
            'mechanics' => [['mechanic_id' => $a->id], ['mechanic_id' => $b->id]],
        ])->assertOk()->json('data'))->pluck('share_percentage', 'mechanic_id');

        $this->assertSame(['50.00', '50.00'], $shares->values()->all());
    }

    public function test_assignment_validation_and_business_rules(): void
    {
        $this->actingAsAdmin();

        $juan = $this->createMechanic();
        $carlos = $this->createMechanic();
        $onLeave = $this->createMechanic(['status' => 'on_leave']);
        $workOrder = $this->workOrderWithJobs($juan);
        $labor = $this->itemOfType($workOrder, 'labor');
        $part = $this->itemOfType($workOrder, 'part');
        $url = $this->mechanicsUrl($workOrder, $labor);

        $this->putJson($url, ['mechanics' => [
            ['mechanic_id' => $juan->id, 'share_percentage' => 60],
            ['mechanic_id' => $carlos->id, 'share_percentage' => 30],
        ]])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_ITEM_SHARES_INVALID');

        $this->putJson($url, ['mechanics' => [
            ['mechanic_id' => $juan->id, 'share_percentage' => 60],
            ['mechanic_id' => $carlos->id],
        ]])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_ITEM_SHARES_INVALID');

        $this->putJson($url, ['mechanics' => [['mechanic_id' => $onLeave->id]]])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'MECHANIC_NOT_ACTIVE');

        $this->putJson($url, ['mechanics' => [['mechanic_id' => $juan->id], ['mechanic_id' => $juan->id]]])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR');

        $this->putJson($url, ['mechanics' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['mechanics']);

        $this->putJson($this->mechanicsUrl($workOrder, $part), ['mechanics' => [['mechanic_id' => $juan->id]]])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_ITEM_NOT_COMMISSIONABLE');

        // Nada de lo anterior modificó la asignación original.
        $this->getJson($url)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.mechanic_id', $juan->id);
    }

    public function test_assignments_are_frozen_for_cancelled_items_and_closed_work_orders(): void
    {
        $this->actingAsAdmin();

        $juan = $this->createMechanic();
        $workOrder = $this->workOrderWithJobs($juan);
        $labor = $this->itemOfType($workOrder, 'labor');
        $service = $this->itemOfType($workOrder, 'service');

        $this->startWorkOrder($workOrder);
        $this->postJson("/api/work-orders/{$workOrder['id']}/items/{$service['id']}/cancel")->assertOk();

        $this->putJson($this->mechanicsUrl($workOrder, $service), ['mechanics' => [['mechanic_id' => $juan->id]]])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_ITEM_MECHANICS_NOT_EDITABLE');

        $this->completeItems($workOrder, ['labor', 'part']);
        $this->postJson("/api/work-orders/{$workOrder['id']}/complete")
            ->assertOk()
            ->assertJsonPath('data.commission_summary.is_final', true)
            // El servicio cancelado no genera comisión.
            ->assertJsonPath('data.commission_summary.total_commission', '0.00');

        $this->putJson($this->mechanicsUrl($workOrder, $labor), ['mechanics' => [['mechanic_id' => $juan->id]]])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_ITEM_MECHANICS_NOT_EDITABLE');
    }

    public function test_commission_percentage_is_a_snapshot(): void
    {
        $this->actingAsAdmin();

        $juan = $this->createMechanic(['commission_percentage' => 10]);
        $carlos = $this->createMechanic(['commission_percentage' => 5]);
        $workOrder = $this->workOrderWithJobs($juan);
        $labor = $this->itemOfType($workOrder, 'labor');

        $juan->update(['commission_percentage' => 50]);

        // Juan ya estaba asignado: conserva su 10%; Carlos entra con su % actual.
        $lines = collect($this->putJson($this->mechanicsUrl($workOrder, $labor), [
            'mechanics' => [['mechanic_id' => $juan->id], ['mechanic_id' => $carlos->id]],
        ])->assertOk()->json('data'))->keyBy('mechanic_id');

        $this->assertSame('10.00', $lines[$juan->id]['commission_percentage']);
        $this->assertSame('5.00', $lines[$carlos->id]['commission_percentage']);
    }

    public function test_changing_the_responsible_reassigns_its_jobs(): void
    {
        $this->actingAsAdmin();

        $juan = $this->createMechanic(['commission_percentage' => 10]);
        $pedro = $this->createMechanic(['commission_percentage' => 12]);
        $workOrder = $this->workOrderWithJobs($juan);

        $updated = $this->patchJson("/api/work-orders/{$workOrder['id']}", ['mechanic_id' => $pedro->id])
            ->assertOk()
            ->json('data');

        $labor = $this->itemOfType($updated, 'labor');

        $this->assertSame($pedro->id, $labor['mechanics'][0]['mechanic_id']);
        $this->assertSame('12.00', $labor['mechanics'][0]['commission_percentage']);
    }

    public function test_commission_report_lists_completed_jobs_only(): void
    {
        $this->actingAsAdmin();

        $juan = $this->createMechanic(['commission_percentage' => 10]);
        $carlos = $this->createMechanic(['commission_percentage' => 8]);
        $workOrder = $this->workOrderWithJobs($juan);
        $labor = $this->itemOfType($workOrder, 'labor');
        $service = $this->itemOfType($workOrder, 'service');

        $this->putJson($this->mechanicsUrl($workOrder, $labor), ['mechanics' => [
            ['mechanic_id' => $juan->id, 'share_percentage' => 70],
            ['mechanic_id' => $carlos->id, 'share_percentage' => 30],
        ]])->assertOk();

        // Antes de completar la OT no hay nada liquidable.
        $this->getJson("/api/mechanics/{$juan->id}/commissions")
            ->assertOk()
            ->assertJsonPath('data.total_commission', '0.00')
            ->assertJsonCount(0, 'data.lines');

        $this->startWorkOrder($workOrder);
        $this->postJson("/api/work-orders/{$workOrder['id']}/items/{$service['id']}/cancel")->assertOk();
        $this->completeItems($workOrder, ['labor', 'part']);
        $this->postJson("/api/work-orders/{$workOrder['id']}/complete")->assertOk();

        $this->getJson("/api/mechanics/{$juan->id}/commissions")
            ->assertOk()
            ->assertJsonPath('data.total_commission', '14000.00')
            ->assertJsonCount(1, 'data.lines')
            ->assertJsonPath('data.lines.0.work_order_code', $workOrder['code'])
            ->assertJsonPath('data.lines.0.item_total', '200000.00')
            ->assertJsonPath('data.lines.0.share_percentage', '70.00');

        $this->getJson("/api/mechanics/{$carlos->id}/commissions")
            ->assertJsonPath('data.total_commission', '4800.00');

        $this->getJson("/api/mechanics/{$juan->id}/commissions?date_from=2020-01-01&date_to=2020-01-31")
            ->assertOk()
            ->assertJsonCount(0, 'data.lines');

        $this->getJson("/api/mechanics/{$juan->id}/commissions?date_from=2026-02-01&date_to=2026-01-01")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR');

        // Un mecánico ve solo sus propias comisiones.
        Sanctum::actingAs($juan->user);

        $this->getJson("/api/mechanics/{$juan->id}/commissions")
            ->assertOk()
            ->assertJsonPath('data.total_commission', '14000.00');

        $this->getJson("/api/mechanics/{$carlos->id}/commissions")
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');
    }

    public function test_mechanic_can_view_but_not_change_assignments(): void
    {
        $this->actingAsAdmin();

        $juan = $this->createMechanic();
        $workOrder = $this->workOrderWithJobs($juan);
        $labor = $this->itemOfType($workOrder, 'labor');

        Sanctum::actingAs($juan->user);

        $this->getJson($this->mechanicsUrl($workOrder, $labor))->assertOk();

        $this->putJson($this->mechanicsUrl($workOrder, $labor), ['mechanics' => [['mechanic_id' => $juan->id]]])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');
    }

    /**
     * Inicia y completa los ítems de los tipos indicados (la OT debe estar en progreso).
     */
    private function completeItems(array $workOrder, array $types): void
    {
        foreach ($workOrder['items'] as $item) {
            if (!in_array($item['type']['value'], $types, true)) {
                continue;
            }

            $this->postJson("/api/work-orders/{$workOrder['id']}/items/{$item['id']}/start")->assertOk();
            $this->postJson("/api/work-orders/{$workOrder['id']}/items/{$item['id']}/complete")->assertOk();
        }
    }
}
