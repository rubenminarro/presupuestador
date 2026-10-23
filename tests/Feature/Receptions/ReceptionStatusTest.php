<?php

namespace Tests\Feature\Receptions;

use App\Models\Budget;
use App\Models\Reception;
use App\Models\ServiceCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\WorkOrderParts\WorkOrderPartsTestCase;

/**
 * El estado de la recepción se deriva del flujo (presupuestos y OT) y se
 * cancela a mano; las recepciones cerradas no admiten trabajo nuevo.
 */
class ReceptionStatusTest extends WorkOrderPartsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
    }

    private function labor(): array
    {
        return ['type' => 'labor', 'description' => 'Trabajo', 'quantity' => 1, 'unit_price' => 1000];
    }

    private function receptionOf(int $budgetId): Reception
    {
        return Reception::findOrFail(Budget::findOrFail($budgetId)->reception_id);
    }

    private function assertReceptionStatus(Reception $reception, string $value, string $label): void
    {
        $this->getJson("/api/receptions/{$reception->id}")
            ->assertOk()
            ->assertJsonPath('data.status.value', $value)
            ->assertJsonPath('data.status.label', $label);
    }

    /**
     * Presupuesto aprobado adicional sobre la misma recepción.
     */
    private function approvedBudgetFor(Reception $reception): int
    {
        $budgetId = $this->postJson('/api/budgets', ['reception_id' => $reception->id])->assertCreated()->json('data.id');
        $this->postJson("/api/budgets/{$budgetId}/items", $this->labor())->assertCreated();
        $this->approveBudget($budgetId);

        return $budgetId;
    }

    public function test_status_follows_the_work_flow(): void
    {
        $budgetId = $this->createBudgetWithItems([$this->labor()]);
        $reception = $this->receptionOf($budgetId);

        // Presupuestar pone la recepción en proceso.
        $this->assertReceptionStatus($reception, 'in_progress', 'En proceso');

        $this->approveBudget($budgetId);
        $workOrder = $this->createWorkOrder($budgetId);
        $this->assertReceptionStatus($reception, 'in_progress', 'En proceso');

        $this->startWorkOrder($workOrder);
        $this->assertReceptionStatus($reception, 'repair', 'En reparación');

        $this->postJson("/api/work-orders/{$workOrder['id']}/pause")->assertOk();
        $this->assertReceptionStatus($reception, 'repair', 'En reparación');

        $this->postJson("/api/work-orders/{$workOrder['id']}/resume")->assertOk();
        $this->completeAllItems($workOrder);
        $this->postJson("/api/work-orders/{$workOrder['id']}/complete")->assertOk();

        $this->assertReceptionStatus($reception, 'completed', 'Terminado');
    }

    public function test_deleting_the_only_budget_returns_the_reception_to_pending(): void
    {
        $budgetId = $this->createBudgetWithItems([$this->labor()]);
        $reception = $this->receptionOf($budgetId);

        $this->deleteJson("/api/budgets/{$budgetId}")->assertOk();

        $this->assertReceptionStatus($reception, 'pending', 'Pendiente');
    }

    public function test_status_considers_every_work_order_of_the_reception(): void
    {
        $budgetId = $this->createBudgetWithItems([$this->labor()]);
        $reception = $this->receptionOf($budgetId);
        $this->approveBudget($budgetId);

        $first = $this->createWorkOrder($budgetId);
        $this->startWorkOrder($first);
        $this->completeAllItems($first);
        $this->postJson("/api/work-orders/{$first['id']}/complete")->assertOk();

        // Trabajo adicional: una OT pendiente vuelve a poner la recepción en proceso.
        $second = $this->createWorkOrder($this->approvedBudgetFor($reception));
        $this->assertReceptionStatus($reception, 'in_progress', 'En proceso');

        // Cancelada la adicional, queda terminada.
        $this->postJson("/api/work-orders/{$second['id']}/cancel")->assertOk();
        $this->assertReceptionStatus($reception, 'completed', 'Terminado');
    }

    public function test_reception_can_be_cancelled_only_without_active_work_orders(): void
    {
        $budgetId = $this->createBudgetWithItems([$this->labor()]);
        $reception = $this->receptionOf($budgetId);
        $this->approveBudget($budgetId);
        $workOrder = $this->createWorkOrder($budgetId);

        $cancel = "/api/receptions/{$reception->id}/cancel";

        $this->postJson($cancel, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);

        $this->postJson($cancel, ['reason' => 'El cliente desistió'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'RECEPTION_HAS_ACTIVE_WORK_ORDERS');

        $this->postJson("/api/work-orders/{$workOrder['id']}/cancel")->assertOk();

        $this->postJson($cancel, ['reason' => 'El cliente desistió'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'cancelled')
            ->assertJsonPath('data.status.label', 'Cancelado')
            ->assertJsonPath('data.cancellation_reason', 'El cliente desistió');

        $this->assertNotNull($reception->fresh()->cancelled_at);

        $this->postJson($cancel, ['reason' => 'Otra vez'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'RECEPTION_NOT_CANCELLABLE');
    }

    public function test_closed_receptions_do_not_accept_new_work(): void
    {
        $budgetId = $this->createBudgetWithItems([$this->labor()]);
        $reception = $this->receptionOf($budgetId);

        // Un presupuesto aprobado sin OT antes de cancelar.
        $this->approveBudget($budgetId);

        $this->postJson("/api/receptions/{$reception->id}/cancel", ['reason' => 'El cliente desistió'])->assertOk();

        $this->postJson('/api/budgets', ['reception_id' => $reception->id])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'RECEPTION_CLOSED');

        $this->postJson('/api/diagnostics', [
            'reception_id' => $reception->id,
            'mechanic_id' => $this->createMechanic()->id,
            'requires_parts' => false,
            'requires_repair' => true,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'RECEPTION_CLOSED');

        $this->postJson('/api/work-orders', [
            'budget_id' => $budgetId,
            'mechanic_id' => $this->createMechanic()->id,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'RECEPTION_CLOSED');

        // El estado cancelado no se recalcula.
        $this->assertReceptionStatus($reception, 'cancelled', 'Cancelado');
    }

    public function test_cancelling_cancels_open_budgets_and_freezes_the_reception(): void
    {
        $draftId = $this->createBudgetWithItems([$this->labor()]);
        $reception = $this->receptionOf($draftId);
        $sentId = $this->postJson('/api/budgets', ['reception_id' => $reception->id])->assertCreated()->json('data.id');
        $this->postJson("/api/budgets/{$sentId}/items", $this->labor())->assertCreated();
        $this->postJson("/api/budgets/{$sentId}/send")->assertOk();
        $approvedId = $this->approvedBudgetFor($reception);

        $this->postJson("/api/receptions/{$reception->id}/cancel", ['reason' => 'El cliente desistió'])->assertOk();

        // Borrador y enviado se cancelan; el aprobado (sin OT) queda como está.
        $this->assertSame('cancelled', Budget::findOrFail($draftId)->status->value);
        $this->assertSame('cancelled', Budget::findOrFail($sentId)->status->value);
        $this->assertSame('approved', Budget::findOrFail($approvedId)->status->value);

        $this->patchJson("/api/receptions/{$reception->id}", ['observations' => 'Nota posterior'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'RECEPTION_CLOSED');
        Storage::fake('public');
        $this->postJson("/api/receptions/{$reception->id}/photos", ['photos' => [['file' => UploadedFile::fake()->image('frente.jpg')]]])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'RECEPTION_CLOSED');
    }

    public function test_budgets_of_a_closed_reception_cannot_change(): void
    {
        $budgetId = $this->createBudgetWithItems([$this->labor()]);
        $reception = $this->receptionOf($budgetId);

        // Cerrada por fuera del flujo (p. ej. entregada con un borrador complementario sin enviar).
        $reception->update(['status' => \App\Enums\ReceptionStatus::DELIVERED]);

        $this->patchJson("/api/budgets/{$budgetId}", ['notes' => 'Cambio'])->assertStatus(422)->assertJsonPath('error_code', 'RECEPTION_CLOSED');
        $this->postJson("/api/budgets/{$budgetId}/items", $this->labor())->assertStatus(422)->assertJsonPath('error_code', 'RECEPTION_CLOSED');
        $this->postJson("/api/budgets/{$budgetId}/send")->assertStatus(422)->assertJsonPath('error_code', 'RECEPTION_CLOSED');
        $this->deleteJson("/api/budgets/{$budgetId}")->assertStatus(422)->assertJsonPath('error_code', 'RECEPTION_CLOSED');

        // Cancelarlo sí: deja de estar pendiente.
        $this->postJson("/api/budgets/{$budgetId}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');
    }

    public function test_a_processed_reception_cannot_change_categories_or_be_deleted(): void
    {
        $budgetId = $this->createBudgetWithItems([$this->labor()]);
        $reception = $this->receptionOf($budgetId);

        $category = ServiceCategory::create(['code' => 'mechanical', 'name' => 'Mecánica']);

        $this->patchJson("/api/receptions/{$reception->id}", ['service_category_ids' => [$category->id]])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'RECEPTION_CATEGORIES_NOT_EDITABLE');
        $this->deleteJson("/api/receptions/{$reception->id}")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'RECEPTION_NOT_DELETABLE');
    }

    public function test_mechanic_cannot_cancel_receptions(): void
    {
        $reception = $this->receptionOf($this->createBudgetWithItems([$this->labor()]));

        $this->actingAsMechanic();

        $this->postJson("/api/receptions/{$reception->id}/cancel", ['reason' => 'El cliente desistió'])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');
    }
}
