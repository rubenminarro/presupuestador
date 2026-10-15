<?php

namespace Tests\Feature\Services;

use App\Models\Mechanic;
use App\Models\ServiceCategory;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\WorkOrderParts\WorkOrderPartsTestCase;

class LaborTimeTest extends WorkOrderPartsTestCase
{
    private array $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();

        $category = ServiceCategory::create(['code' => 'mech', 'name' => 'Mecánica']);

        $this->service = $this->postJson('/api/services', [
            'service_category_id' => $category->id,
            'name' => 'Alineación y balanceo',
            'standard_price' => 120000,
            'estimated_hours' => 1.5,
        ])->assertCreated()->json('data');
    }

    /**
     * OT con mano de obra (200.000, 3 h estimadas) y servicio × 2 (3 h estimadas).
     */
    private function workOrderWithJobs(Mechanic $responsible): array
    {
        $budgetId = $this->createBudgetWithItems([
            ['type' => 'labor', 'description' => 'Cambio de embrague', 'quantity' => 1, 'unit_price' => 200000, 'estimated_hours' => 3],
            ['type' => 'service', 'service_id' => $this->service['id'], 'description' => 'Alineación', 'quantity' => 2],
        ]);

        $this->approveBudget($budgetId);

        return $this->postJson('/api/work-orders', [
            'budget_id' => $budgetId,
            'mechanic_id' => $responsible->id,
        ])->assertCreated()->json('data');
    }

    private function item(array $workOrder, string $type): array
    {
        return collect($workOrder['items'])->firstWhere('type.value', $type);
    }

    private function hoursUrl(array $workOrder, array $item, Mechanic $mechanic): string
    {
        return "/api/work-orders/{$workOrder['id']}/items/{$item['id']}/mechanics/{$mechanic->id}/hours";
    }

    public function test_service_exposes_estimated_hours(): void
    {
        $this->assertSame('1.50', $this->service['estimated_hours']);

        $this->getJson("/api/services/{$this->service['id']}")
            ->assertJsonPath('data.estimated_hours', '1.50');
    }

    public function test_budget_items_carry_estimated_hours(): void
    {
        $budgetId = $this->createBudgetWithItems([]);
        $url = "/api/budgets/{$budgetId}/items";

        // Servicio: catálogo (1.5) × cantidad (2).
        $service = $this->postJson($url, [
            'type' => 'service',
            'service_id' => $this->service['id'],
            'description' => 'Alineación',
            'quantity' => 2,
        ])
            ->assertCreated()
            ->assertJsonPath('data.estimated_hours', '3.00')
            ->json('data');

        // Al cambiar la cantidad se recalcula.
        $this->patchJson("{$url}/{$service['id']}", ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('data.estimated_hours', '4.50');

        // Un valor informado prevalece sobre el del catálogo.
        $this->postJson($url, [
            'type' => 'service',
            'service_id' => $this->service['id'],
            'description' => 'Alineación compleja',
            'quantity' => 1,
            'estimated_hours' => 2.25,
        ])
            ->assertCreated()
            ->assertJsonPath('data.estimated_hours', '2.25');

        // Mano de obra: opcional.
        $this->postJson($url, ['type' => 'labor', 'description' => 'Con estimado', 'quantity' => 1, 'unit_price' => 1, 'estimated_hours' => 4])
            ->assertCreated()
            ->assertJsonPath('data.estimated_hours', '4.00');

        $this->postJson($url, ['type' => 'labor', 'description' => 'Sin estimado', 'quantity' => 1, 'unit_price' => 1])
            ->assertCreated()
            ->assertJsonPath('data.estimated_hours', null);

        // Repuesto: no admite tiempo estimado.
        $this->postJson($url, ['type' => 'part', 'description' => 'Filtro', 'quantity' => 1, 'unit_price' => 1, 'estimated_hours' => 1])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['estimated_hours']);
    }

    public function test_labor_summary_without_hours_is_unknown_not_zero(): void
    {
        $juan = $this->createMechanic(['hour_cost' => 45000]);
        $workOrder = $this->workOrderWithJobs($juan);

        $this->assertSame('3.00', $this->item($workOrder, 'labor')['estimated_hours']);

        $summary = $workOrder['labor_summary'];

        $this->assertSame('6.00', $summary['estimated_hours']);
        $this->assertNull($summary['actual_hours']);
        $this->assertNull($summary['labor_cost']);
        $this->assertSame(2, $summary['assignments_without_hours']);
        $this->assertFalse($summary['is_complete']);
    }

    public function test_mechanic_logs_own_hours_and_cost_is_snapshotted(): void
    {
        $juan = $this->createMechanic(['hour_cost' => 45000]);
        $workOrder = $this->workOrderWithJobs($juan);
        $labor = $this->item($workOrder, 'labor');

        // Con la OT pendiente todavía no hay trabajo realizado.
        $this->patchJson($this->hoursUrl($workOrder, $labor, $juan), ['hours' => 2])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_ITEM_HOURS_NOT_EDITABLE');

        $this->startWorkOrder($workOrder);

        Sanctum::actingAs($juan->user);

        $this->patchJson($this->hoursUrl($workOrder, $labor, $juan), ['hours' => 2.5])
            ->assertOk()
            ->assertJsonPath('data.hours', '2.50')
            ->assertJsonPath('data.hour_cost', '45000.00')
            ->assertJsonPath('data.labor_cost', '112500.00');

        // Un aumento posterior no cambia el costo ya copiado.
        $juan->update(['hour_cost' => 60000]);

        $this->patchJson($this->hoursUrl($workOrder, $labor, $juan), ['hours' => 3])
            ->assertOk()
            ->assertJsonPath('data.hour_cost', '45000.00')
            ->assertJsonPath('data.labor_cost', '135000.00');

        $this->actingAsAdmin();

        $summary = $this->getJson("/api/work-orders/{$workOrder['id']}")->json('data.labor_summary');

        $this->assertSame('3.00', $summary['actual_hours']);
        $this->assertSame('135000.00', $summary['labor_cost']);
        $this->assertSame(1, $summary['assignments_without_hours']);
    }

    public function test_mechanic_cannot_log_hours_for_others(): void
    {
        $juan = $this->createMechanic(['hour_cost' => 45000]);
        $carlos = $this->createMechanic(['hour_cost' => 35000]);
        $workOrder = $this->workOrderWithJobs($juan);
        $labor = $this->item($workOrder, 'labor');

        $this->putJson("/api/work-orders/{$workOrder['id']}/items/{$labor['id']}/mechanics", [
            'mechanics' => [['mechanic_id' => $juan->id], ['mechanic_id' => $carlos->id]],
        ])->assertOk();

        $this->startWorkOrder($workOrder);

        Sanctum::actingAs($carlos->user);

        $this->patchJson($this->hoursUrl($workOrder, $labor, $juan), ['hours' => 1])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->patchJson($this->hoursUrl($workOrder, $labor, $carlos), ['hours' => 1])->assertOk();

        // El administrador puede cargar las de cualquiera.
        $this->actingAsAdmin();

        $this->patchJson($this->hoursUrl($workOrder, $labor, $juan), ['hours' => 2])
            ->assertOk()
            ->assertJsonPath('data.labor_cost', '90000.00');
    }

    public function test_hours_rules_and_validation(): void
    {
        $juan = $this->createMechanic(['hour_cost' => 45000]);
        $noCost = $this->createMechanic();
        $outsider = $this->createMechanic();
        $workOrder = $this->workOrderWithJobs($juan);
        $labor = $this->item($workOrder, 'labor');
        $service = $this->item($workOrder, 'service');

        $this->putJson("/api/work-orders/{$workOrder['id']}/items/{$labor['id']}/mechanics", [
            'mechanics' => [['mechanic_id' => $juan->id], ['mechanic_id' => $noCost->id]],
        ])->assertOk();

        $this->startWorkOrder($workOrder);

        $url = $this->hoursUrl($workOrder, $labor, $juan);

        $this->patchJson($url, [])->assertStatus(422)->assertJsonValidationErrors(['hours']);
        $this->patchJson($url, ['hours' => 0])->assertStatus(422)->assertJsonValidationErrors(['hours']);
        $this->patchJson($url, ['hours' => 1.234])->assertStatus(422)->assertJsonValidationErrors(['hours']);

        // Mecánico no asignado al trabajo → 404 (scoped binding).
        $this->patchJson($this->hoursUrl($workOrder, $labor, $outsider), ['hours' => 1])
            ->assertNotFound()
            ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');

        // Sin costo por hora configurado, el costo queda desconocido.
        $this->patchJson($this->hoursUrl($workOrder, $labor, $noCost), ['hours' => 1])
            ->assertOk()
            ->assertJsonPath('data.hour_cost', null)
            ->assertJsonPath('data.labor_cost', null);

        // null borra horas y costo.
        $this->patchJson($url, ['hours' => 2])->assertOk();
        $this->patchJson($url, ['hours' => null])
            ->assertOk()
            ->assertJsonPath('data.hours', null)
            ->assertJsonPath('data.labor_cost', null);

        // Reasignar conserva las horas de quien sigue asignado.
        $this->patchJson($url, ['hours' => 2])->assertOk();
        $this->putJson("/api/work-orders/{$workOrder['id']}/items/{$labor['id']}/mechanics", [
            'mechanics' => [
                ['mechanic_id' => $juan->id, 'share_percentage' => 80],
                ['mechanic_id' => $noCost->id, 'share_percentage' => 20],
            ],
        ])->assertOk();

        $this->getJson("/api/work-orders/{$workOrder['id']}/items/{$labor['id']}/mechanics")
            ->assertJsonFragment(['mechanic_id' => $juan->id, 'hours' => '2.00']);

        $summary = $this->getJson("/api/work-orders/{$workOrder['id']}")->json('data.labor_summary');
        $this->assertSame(1, $summary['assignments_without_cost']);

        // Trabajo cancelado.
        $this->postJson("/api/work-orders/{$workOrder['id']}/items/{$service['id']}/cancel")->assertOk();
        $this->patchJson($this->hoursUrl($workOrder, $service, $juan), ['hours' => 1])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_ITEM_HOURS_NOT_EDITABLE');

        // OT completada.
        $this->postJson("/api/work-orders/{$workOrder['id']}/items/{$labor['id']}/start")->assertOk();
        $this->postJson("/api/work-orders/{$workOrder['id']}/items/{$labor['id']}/complete")->assertOk();
        $this->postJson("/api/work-orders/{$workOrder['id']}/complete")->assertOk();

        $this->patchJson($url, ['hours' => 5])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_ITEM_HOURS_NOT_EDITABLE');
    }
}
