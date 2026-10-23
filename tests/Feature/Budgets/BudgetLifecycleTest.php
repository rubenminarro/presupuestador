<?php

namespace Tests\Feature\Budgets;

use App\Models\Budget;
use App\Models\ServiceCategory;
use Tests\Feature\WorkOrderParts\WorkOrderPartsTestCase;

class BudgetLifecycleTest extends WorkOrderPartsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
    }

    private function labor(int $unitPrice = 100000): array
    {
        return ['type' => 'labor', 'description' => 'Mano de obra', 'quantity' => 1, 'unit_price' => $unitPrice];
    }

    private function draftWithItem(): int
    {
        return $this->createBudgetWithItems([$this->labor()]);
    }

    private function action(int $budgetId, string $action)
    {
        return $this->postJson("/api/budgets/{$budgetId}/{$action}");
    }

    public function test_crud_of_budgets(): void
    {
        $receptionId = Budget::findOrFail($this->draftWithItem())->reception_id;

        $budget = $this->postJson('/api/budgets', ['reception_id' => $receptionId, 'notes' => 'Cliente pide urgencia'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.total', '0.00')
            ->json('data');

        $this->assertMatchesRegularExpression('/^BUD-/', $budget['code']);

        $this->getJson('/api/budgets')->assertOk()->assertJsonPath('meta.pagination.total', 2);
        $this->getJson("/api/budgets/{$budget['id']}")->assertOk()->assertJsonPath('data.notes', 'Cliente pide urgencia');

        $this->patchJson("/api/budgets/{$budget['id']}", ['notes' => 'Sin urgencia'])
            ->assertOk()
            ->assertJsonPath('data.notes', 'Sin urgencia');

        $this->deleteJson("/api/budgets/{$budget['id']}")->assertOk();
        $this->getJson("/api/budgets/{$budget['id']}")->assertNotFound()->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');

        $this->postJson('/api/budgets', [])->assertStatus(422)->assertJsonValidationErrors(['reception_id']);
        $this->postJson('/api/budgets', ['reception_id' => 999999])->assertJsonValidationErrors(['reception_id']);
    }

    public function test_items_recalculate_the_totals(): void
    {
        $budgetId = $this->createBudgetWithItems([]);

        $item = $this->postJson("/api/budgets/{$budgetId}/items", ['type' => 'labor', 'description' => 'Mano de obra', 'quantity' => 2, 'unit_price' => 50000])
            ->assertCreated()
            ->assertJsonPath('data.total', '100000.00')
            ->json('data');

        // IVA 10% sobre el subtotal.
        $this->getJson("/api/budgets/{$budgetId}")
            ->assertOk()
            ->assertJsonPath('data.subtotal', '100000.00')
            ->assertJsonPath('data.tax', '10000.00')
            ->assertJsonPath('data.total', '110000.00')
            ->assertJsonCount(1, 'data.items');

        $this->getJson("/api/budgets/{$budgetId}/items")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/budgets/{$budgetId}/items/{$item['id']}")->assertOk();

        $this->patchJson("/api/budgets/{$budgetId}/items/{$item['id']}", ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('data.total', '150000.00');

        $this->getJson("/api/budgets/{$budgetId}")->assertJsonPath('data.total', '165000.00');

        $this->deleteJson("/api/budgets/{$budgetId}/items/{$item['id']}")->assertOk();

        $this->getJson("/api/budgets/{$budgetId}")
            ->assertJsonPath('data.subtotal', '0.00')
            ->assertJsonPath('data.total', '0.00');
    }

    public function test_detail_includes_catalog_items_and_vehicle_and_items_notes_can_be_cleared(): void
    {
        $part = $this->createPartViaApi(null, ['name' => 'Filtro de aceite']);
        $service = $this->postJson('/api/services', [
            'service_category_id' => ServiceCategory::create(['code' => 'mech', 'name' => 'Mecánica'])->id,
            'name' => 'Alineación',
            'standard_price' => 120000,
        ])->assertCreated()->json('data');

        $budgetId = $this->createBudgetWithItems([
            ['type' => 'part', 'part_id' => $part['id'], 'description' => 'Filtro 100%', 'quantity' => 1, 'unit_price' => 50000],
            ['type' => 'service', 'service_id' => $service['id'], 'description' => 'Alineación', 'quantity' => 1, 'notes' => 'Con balanceo'],
        ]);
        $vehicle = Budget::findOrFail($budgetId)->reception->vehicle->load(['brand', 'vehicleModel']);

        $budget = $this->getJson("/api/budgets/{$budgetId}")
            ->assertOk()
            ->assertJsonPath('data.reception.vehicle.brand', $vehicle->brand->name)
            ->assertJsonPath('data.reception.vehicle.model', $vehicle->vehicleModel->name)
            ->json('data');

        $items = collect($budget['items'])->keyBy('type');
        $this->assertSame('Filtro de aceite', $items['part']['part']['name']);
        $this->assertSame($part['code'], $items['part']['part']['code']);
        $this->assertSame('Alineación', $items['service']['service']['name']);

        $this->getJson('/api/budgets')
            ->assertOk()
            ->assertJsonPath('data.0.reception.vehicle.model', $vehicle->vehicleModel->name);

        // Editar acepta lo mismo que el alta (descripción con %) y vacía las notas.
        $this->patchJson("/api/budgets/{$budgetId}/items/{$items['part']['id']}", ['description' => 'Filtro 100% original'])
            ->assertOk()
            ->assertJsonPath('data.description', 'Filtro 100% original');

        $this->patchJson("/api/budgets/{$budgetId}/items/{$items['service']['id']}", ['notes' => null])
            ->assertOk()
            ->assertJsonPath('data.notes', null);
    }

    public function test_totals_use_exact_decimals_with_half_up_rounding(): void
    {
        $budgetId = $this->createBudgetWithItems([]);

        // 1.15 × 100.10 = 115.115 (en float da 115.11499…).
        $this->postJson("/api/budgets/{$budgetId}/items", ['type' => 'labor', 'description' => 'Ajuste', 'quantity' => 1.15, 'unit_price' => 100.10])
            ->assertCreated()
            ->assertJsonPath('data.total', '115.12');

        $this->postJson("/api/budgets/{$budgetId}/items", ['type' => 'labor', 'description' => 'Limpieza', 'quantity' => 3, 'unit_price' => 0.15])
            ->assertCreated()
            ->assertJsonPath('data.total', '0.45');

        // Subtotal 115.57; IVA 10% = 11.557 → 11.56.
        $this->getJson("/api/budgets/{$budgetId}")
            ->assertJsonPath('data.subtotal', '115.57')
            ->assertJsonPath('data.tax', '11.56')
            ->assertJsonPath('data.total', '127.13');
    }

    public function test_reject_reopen_and_cancel(): void
    {
        $budgetId = $this->draftWithItem();

        $this->action($budgetId, 'approve')->assertStatus(422)->assertJsonPath('error_code', 'BUDGET_NOT_APPROVABLE');
        $this->action($budgetId, 'reject')->assertStatus(422)->assertJsonPath('error_code', 'BUDGET_NOT_REJECTABLE');
        $this->action($budgetId, 'reopen')->assertStatus(422)->assertJsonPath('error_code', 'BUDGET_NOT_REOPENABLE');

        $this->action($budgetId, 'send')->assertOk()->assertJsonPath('data.status', 'sent');
        $this->action($budgetId, 'reject')->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->action($budgetId, 'approve')->assertStatus(422)->assertJsonPath('error_code', 'BUDGET_NOT_APPROVABLE');

        $this->action($budgetId, 'reopen')
            ->assertOk()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.approved_at', null);

        // Reabierto vuelve a ser editable.
        $this->postJson("/api/budgets/{$budgetId}/items", $this->labor(5000))->assertCreated();

        $this->action($budgetId, 'send')->assertOk();
        $this->action($budgetId, 'cancel')->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->action($budgetId, 'cancel')->assertStatus(422)->assertJsonPath('error_code', 'BUDGET_NOT_CANCELLABLE');
        $this->action($budgetId, 'send')->assertStatus(422)->assertJsonPath('error_code', 'BUDGET_NOT_SENDABLE');

        // Un borrador también puede cancelarse; uno aprobado no.
        $this->action($this->draftWithItem(), 'cancel')->assertOk()->assertJsonPath('data.status', 'cancelled');

        $approved = $this->draftWithItem();
        $this->approveBudget($approved);
        $this->action($approved, 'cancel')->assertStatus(422)->assertJsonPath('error_code', 'BUDGET_NOT_CANCELLABLE');
    }

    public function test_only_drafts_are_editable(): void
    {
        $budgetId = $this->draftWithItem();
        $itemId = Budget::findOrFail($budgetId)->items()->value('id');

        $this->action($budgetId, 'send')->assertOk();

        $this->patchJson("/api/budgets/{$budgetId}", ['notes' => 'x'])->assertStatus(422)->assertJsonPath('error_code', 'BUDGET_NOT_EDITABLE');
        $this->postJson("/api/budgets/{$budgetId}/items", $this->labor())->assertStatus(422)->assertJsonPath('error_code', 'BUDGET_NOT_EDITABLE');
        $this->patchJson("/api/budgets/{$budgetId}/items/{$itemId}", ['quantity' => 5])->assertStatus(422)->assertJsonPath('error_code', 'BUDGET_NOT_EDITABLE');
        $this->deleteJson("/api/budgets/{$budgetId}/items/{$itemId}")->assertStatus(422)->assertJsonPath('error_code', 'BUDGET_NOT_EDITABLE');
        $this->deleteJson("/api/budgets/{$budgetId}")->assertStatus(422)->assertJsonPath('error_code', 'BUDGET_NOT_EDITABLE');
    }

    public function test_sending_requires_the_reception_diagnostics_to_be_closed(): void
    {
        $budgetId = $this->draftWithItem();
        $receptionId = Budget::findOrFail($budgetId)->reception_id;
        $diagnostic = fn () => $this->postJson('/api/diagnostics', [
            'reception_id' => $receptionId,
            'requires_parts' => false,
            'requires_repair' => true,
        ])->assertCreated()->json('data.id');

        $pending = $diagnostic();
        $inProgress = $diagnostic();
        $this->patchJson("/api/diagnostics/{$inProgress}", ['status' => 'in_progress'])->assertOk();

        // El borrador se sigue editando; enviarlo no.
        $this->postJson("/api/budgets/{$budgetId}/items", $this->labor(5000))->assertCreated();
        $this->action($budgetId, 'send')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'BUDGET_DIAGNOSTICS_PENDING')
            ->assertJsonPath('message', "La recepción tiene diagnósticos sin terminar (#{$pending}, #{$inProgress}): termínelos o cancélelos antes de enviar el presupuesto.");

        $this->patchJson("/api/diagnostics/{$pending}", ['status' => 'completed'])->assertOk();
        $this->patchJson("/api/diagnostics/{$inProgress}", ['status' => 'cancelled'])->assertOk();

        $this->action($budgetId, 'send')->assertOk()->assertJsonPath('data.status', 'sent');
    }

    public function test_mechanic_cannot_manage_budgets(): void
    {
        $budgetId = $this->draftWithItem();

        $this->actingAsMechanic();

        $this->getJson('/api/budgets')->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
        $this->action($budgetId, 'send')->assertForbidden();
        $this->deleteJson("/api/budgets/{$budgetId}")->assertForbidden();
    }
}
