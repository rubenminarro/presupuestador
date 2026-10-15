<?php

namespace Tests\Feature\WorkOrders;

use App\Models\Mechanic;
use App\Models\WorkOrderClosing;
use App\Models\WorkOrderCommission;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\WorkOrderParts\WorkOrderPartsTestCase;

class WorkOrderClosingTest extends WorkOrderPartsTestCase
{
    private Mechanic $juan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();

        $this->juan = $this->createMechanic(['commission_percentage' => 10, 'hour_cost' => 200]);
    }

    /**
     * Presupuesto: aceite (catálogo) 2 × 150, mano de obra 1.000 y tornillos
     * fuera de catálogo 50 → presupuestado 1.350. Stock de aceite a costo 100.
     */
    private function startedWorkOrder(bool $withOffCatalogPart = true): array
    {
        $oil = $this->createPartViaApi(null, ['unit' => 'liter']);
        $this->entry($oil['id'], 10, 100, ['type' => 'initial'])->assertCreated();

        $items = [
            ['type' => 'part', 'part_id' => $oil['id'], 'description' => 'Aceite', 'quantity' => 2, 'unit_price' => 150],
            ['type' => 'labor', 'description' => 'Cambio de aceite', 'quantity' => 1, 'unit_price' => 1000],
        ];

        if ($withOffCatalogPart) {
            $items[] = ['type' => 'part', 'description' => 'Tornillos', 'quantity' => 1, 'unit_price' => 50];
        }

        $budgetId = $this->createBudgetWithItems($items);
        $this->approveBudget($budgetId);

        $workOrder = $this->postJson('/api/work-orders', [
            'budget_id' => $budgetId,
            'mechanic_id' => $this->juan->id,
        ])->assertCreated()->json('data');

        $this->startWorkOrder($workOrder);

        // Presupuestado 2 L, utilizado 1,7 L → costo 170, venta 255.
        $this->postJson($this->partsUrl($workOrder, $workOrder['parts'][0]['id'], 'consume'), ['quantity_used' => 1.7])
            ->assertOk();

        return $workOrder;
    }

    private function laborItem(array $workOrder): array
    {
        return collect($workOrder['items'])->firstWhere('type.value', 'labor');
    }

    private function logHours(array $workOrder, float $hours): void
    {
        $labor = $this->laborItem($workOrder);

        $this->patchJson(
            "/api/work-orders/{$workOrder['id']}/items/{$labor['id']}/mechanics/{$this->juan->id}/hours",
            ['hours' => $hours]
        )->assertOk();
    }

    private function complete(array $workOrder): array
    {
        $this->completeAllItems($workOrder);

        return $this->postJson("/api/work-orders/{$workOrder['id']}/complete")
            ->assertOk()
            ->json('data');
    }

    public function test_completing_the_work_order_freezes_the_economic_closing(): void
    {
        $workOrder = $this->startedWorkOrder();

        // Repuesto extra no presupuestado: costo 30, venta 80.
        $filter = $this->createPartViaApi();
        $this->entry($filter['id'], 5, 30)->assertCreated();

        $extra = $this->postJson($this->partsUrl($workOrder), [
            'part_id' => $filter['id'],
            'quantity' => 1,
            'unit_price' => 80,
        ])->assertCreated()->json('data');

        $this->postJson($this->partsUrl($workOrder, $extra['id'], 'consume'), ['quantity_used' => 1])->assertOk();

        // 2 h × 200 = 400 de mano de obra.
        $this->logHours($workOrder, 2);

        $summary = $this->complete($workOrder)['economic_summary'];

        $this->assertTrue($summary['is_final']);
        $this->assertNotNull($summary['closed_at']);
        $this->assertSame(['labor' => '1000.00', 'parts' => '350.00', 'total' => '1350.00'], $summary['budgeted']);
        // Repuestos: 255 + 80 (extra) + 50 (fuera de catálogo).
        $this->assertSame(['labor' => '1000.00', 'parts' => '385.00', 'total' => '1385.00'], $summary['sale']);
        $this->assertSame('35.00', $summary['variance']);
        // Comisión: 1.000 × 100% × 10% = 100.
        $this->assertSame(
            ['parts' => '200.00', 'labor' => '400.00', 'commissions' => '100.00', 'total' => '700.00'],
            $summary['cost']
        );
        $this->assertSame('685.00', $summary['gross_margin']);
        $this->assertSame('49.46', $summary['margin_percentage']);
        // Los tornillos fuera de catálogo no tienen costo conocido.
        $this->assertFalse($summary['is_cost_complete']);

        $this->assertDatabaseHas('work_order_closings', [
            'work_order_id' => $workOrder['id'],
            'sale_total' => '1385.00',
            'gross_margin' => '685.00',
        ]);

        $commission = WorkOrderCommission::sole();
        $this->assertSame($this->juan->id, $commission->mechanic_id);
        $this->assertSame('1000.00', $commission->base_amount);
        $this->assertSame('100.00', $commission->amount);
        $this->assertNull($commission->settled_at);
    }

    public function test_margin_uses_known_costs_when_labor_hours_are_missing(): void
    {
        $workOrder = $this->startedWorkOrder(withOffCatalogPart: false);

        $summary = $this->complete($workOrder)['economic_summary'];

        $this->assertNull($summary['cost']['labor']);
        // 1.255 − (170 + 100 de comisión).
        $this->assertSame('270.00', $summary['cost']['total']);
        $this->assertSame('985.00', $summary['gross_margin']);
        $this->assertFalse($summary['is_cost_complete']);
    }

    public function test_cost_is_complete_with_catalog_parts_and_hours(): void
    {
        $workOrder = $this->startedWorkOrder(withOffCatalogPart: false);
        $this->logHours($workOrder, 1.5);

        $summary = $this->complete($workOrder)['economic_summary'];

        $this->assertSame('300.00', $summary['cost']['labor']);
        $this->assertTrue($summary['is_cost_complete']);
    }

    public function test_open_work_order_shows_a_provisional_summary(): void
    {
        $workOrder = $this->startedWorkOrder(withOffCatalogPart: false);

        $summary = $this->getJson("/api/work-orders/{$workOrder['id']}")
            ->assertOk()
            ->json('data.economic_summary');

        $this->assertFalse($summary['is_final']);
        $this->assertNull($summary['closed_at']);
        $this->assertSame('1255.00', $summary['sale']['total']);
        $this->assertSame(0, WorkOrderClosing::count());
        $this->assertSame(0, WorkOrderCommission::count());
    }

    public function test_closed_summary_is_frozen(): void
    {
        $workOrder = $this->startedWorkOrder(withOffCatalogPart: false);
        $this->complete($workOrder);

        // Cambios posteriores en el catálogo o el mecánico no alteran el cierre.
        $this->juan->update(['commission_percentage' => 50, 'hour_cost' => 999]);
        WorkOrderClosing::where('work_order_id', $workOrder['id'])->update(['sale_total' => '1.00']);

        $this->getJson("/api/work-orders/{$workOrder['id']}")
            ->assertOk()
            ->assertJsonPath('data.economic_summary.is_final', true)
            ->assertJsonPath('data.economic_summary.sale.total', '1.00');
    }

    public function test_mechanic_does_not_see_the_economic_summary(): void
    {
        $workOrder = $this->startedWorkOrder(withOffCatalogPart: false);

        Sanctum::actingAs($this->juan->user);

        $data = $this->getJson("/api/work-orders/{$workOrder['id']}")->assertOk()->json('data');

        $this->assertArrayNotHasKey('economic_summary', $data);
    }

    public function test_commissions_are_settled_by_period(): void
    {
        $this->complete($this->startedWorkOrder(withOffCatalogPart: false));

        $url = "/api/mechanics/{$this->juan->id}/commissions";
        $period = ['date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->endOfMonth()->toDateString()];

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.total_commission', '100.00')
            ->assertJsonPath('data.total_pending', '100.00')
            ->assertJsonPath('data.total_settled', '0.00')
            ->assertJsonPath('data.lines.0.settled_at', null);

        // El mecánico no puede liquidar.
        Sanctum::actingAs($this->juan->user);

        $this->postJson("{$url}/settle", $period)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->actingAsAdmin();

        $this->postJson("{$url}/settle", [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['date_from', 'date_to']);

        $this->postJson("{$url}/settle", $period)
            ->assertOk()
            ->assertJsonPath('data.settled_count', 1)
            ->assertJsonPath('data.settled_total', '100.00');

        $this->getJson("{$url}?status=settled")
            ->assertOk()
            ->assertJsonCount(1, 'data.lines')
            ->assertJsonPath('data.total_settled', '100.00')
            ->assertJsonPath('data.total_pending', '0.00');

        $this->getJson("{$url}?status=pending")
            ->assertOk()
            ->assertJsonCount(0, 'data.lines');

        $this->postJson("{$url}/settle", $period)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'MECHANIC_COMMISSIONS_NOTHING_TO_SETTLE');
    }
}
