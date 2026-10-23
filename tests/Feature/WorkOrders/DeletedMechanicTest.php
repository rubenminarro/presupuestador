<?php

namespace Tests\Feature\WorkOrders;

use App\Models\Budget;
use App\Models\Mechanic;
use App\Models\Reception;
use Tests\Feature\WorkOrderParts\WorkOrderPartsTestCase;

/**
 * Eliminar un mecánico (soft delete) no borra su nombre de lo que ya hizo: la OT, el historial del vehículo y el
 * diagnóstico lo siguen mostrando.
 */
class DeletedMechanicTest extends WorkOrderPartsTestCase
{
    public function test_work_orders_history_and_diagnostics_keep_a_deleted_mechanic(): void
    {
        $this->actingAsAdmin();

        $budgetId = $this->createBudgetWithItems([['type' => 'labor', 'description' => 'Trabajo', 'quantity' => 1, 'unit_price' => 1000]]);
        $reception = Reception::findOrFail(Budget::findOrFail($budgetId)->reception_id);
        $this->approveBudget($budgetId);
        $workOrder = $this->createWorkOrder($budgetId);
        $mechanic = Mechanic::findOrFail($workOrder['mechanic_id']);

        $diagnostic = $this->postJson('/api/diagnostics', [
            'reception_id' => $reception->id,
            'mechanic_id' => $mechanic->id,
            'requires_parts' => false,
            'requires_repair' => true,
        ])->assertCreated()->json('data.id');

        $mechanic->delete();
        $name = 'Juan Mecanico';

        $this->getJson("/api/work-orders/{$workOrder['id']}")
            ->assertOk()
            ->assertJsonPath('data.mechanic.id', $mechanic->id)
            ->assertJsonPath('data.mechanic.user.full_name', $name);

        $this->getJson("/api/vehicles/{$reception->vehicle_id}/history")
            ->assertOk()
            ->assertJsonPath('data.timeline.0.work_orders.0.mechanic', $name);

        $this->getJson("/api/diagnostics/{$diagnostic}")
            ->assertOk()
            ->assertJsonPath('data.mechanic.id', $mechanic->id)
            ->assertJsonPath('data.mechanic.user.full_name', $name);
    }
}
