<?php

namespace Tests\Feature\MasterData;

use App\Models\Budget;
use App\Models\CheckListItem;
use App\Models\Mechanic;
use App\Models\MaintenancePlan;
use App\Models\Reception;
use Tests\Feature\Receptions\ReceptionTestCase;

/**
 * Datos maestros ya usados (decisiones del 2026-10-22): mecánicos, ítems de checklist y vehículos no se eliminan;
 * un servicio usado no cambia de nombre ni se elimina si lo usa un plan de mantenimiento.
 */
class InUseDeletionTest extends ReceptionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
    }

    public function test_a_mechanic_with_work_is_deactivated_not_deleted(): void
    {
        $budgetId = $this->createBudgetWithItems([['type' => 'labor', 'description' => 'Trabajo', 'quantity' => 1, 'unit_price' => 1000]]);
        $this->approveBudget($budgetId);
        $busy = Mechanic::findOrFail($this->createWorkOrder($budgetId)['mechanic_id']);

        $this->deleteJson("/api/mechanics/{$busy->id}")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'MECHANIC_IN_USE');
        $this->assertNotSoftDeleted($busy);

        // Uno sin trabajos se elimina como antes.
        $idle = $this->createMechanic();
        $this->deleteJson("/api/mechanics/{$idle->id}")->assertOk();
        $this->assertSoftDeleted($idle);
    }

    public function test_a_mechanic_with_only_a_diagnostic_is_in_use(): void
    {
        $mechanic = $this->createMechanic();
        $this->postJson('/api/diagnostics', [
            'reception_id' => $this->createReception()['id'],
            'mechanic_id' => $mechanic->id,
            'requires_parts' => false,
            'requires_repair' => true,
        ])->assertCreated();

        $this->deleteJson("/api/mechanics/{$mechanic->id}")->assertStatus(409)->assertJsonPath('error_code', 'MECHANIC_IN_USE');
    }

    public function test_a_used_service_keeps_its_name_and_a_planned_one_is_not_deleted(): void
    {
        $service = $this->postJson('/api/services', [
            'service_category_id' => $this->mechanical->id,
            'name' => 'Cambio de aceite',
            'standard_price' => 100000,
            'estimated_hours' => 1,
        ])->assertCreated()->json('data');

        // Sin uso, el nombre se edita.
        $this->patchJson("/api/services/{$service['id']}", ['name' => 'Cambio de aceite y filtro'])->assertOk();

        $budgetId = $this->createBudgetWithItems([]);
        $this->postJson("/api/budgets/{$budgetId}/items", [
            'type' => 'service', 'service_id' => $service['id'], 'description' => 'Cambio de aceite y filtro', 'quantity' => 1,
        ])->assertCreated();

        $this->patchJson("/api/services/{$service['id']}", ['name' => 'Otro nombre'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'SERVICE_NAME_NOT_EDITABLE');
        // El mismo nombre, el precio y las horas sí.
        $this->patchJson("/api/services/{$service['id']}", ['name' => 'Cambio de aceite y filtro', 'standard_price' => 120000, 'estimated_hours' => 1.5])
            ->assertOk()
            ->assertJsonPath('data.standard_price', '120000.00');

        // Sin presupuestos pero con un plan de mantenimiento.
        $planned = $this->postJson('/api/services', [
            'service_category_id' => $this->mechanical->id,
            'name' => 'Rotacion',
            'standard_price' => 50000,
        ])->assertCreated()->json('data');
        MaintenancePlan::create(['name' => 'Rotacion de neumaticos', 'service_id' => $planned['id'], 'interval_km' => 10000]);

        $this->deleteJson("/api/services/{$planned['id']}")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'SERVICE_IN_USE');
    }

    public function test_a_used_checklist_item_is_deactivated_and_leaves_new_checklists(): void
    {
        $oil = CheckListItem::where('name', 'Nivel de aceite')->firstOrFail();
        $this->createReception();

        $this->deleteJson("/api/checklists/{$oil->id}")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'CHECKLIST_ITEM_IN_USE');
        $this->assertSame('Nivel de aceite', $oil->fresh()->name);

        $this->patchJson("/api/checklists/{$oil->id}", ['is_active' => false])->assertOk()->assertJsonPath('data.is_active', false);
        $this->getJson('/api/checklists?is_active=0')->assertOk()->assertJsonPath('data.0.id', $oil->id);

        // La recepción nueva ya no lo pide; la anterior lo conserva.
        Reception::query()->update(['status' => 'delivered']);
        $next = $this->createReception();
        $names = Reception::findOrFail($next['id'])->checkList->items()->with('checkListItem')->get()->pluck('checkListItem.name')->all();
        $this->assertNotContains('Nivel de aceite', $names);
        $this->assertContains('Luces', $names);

        // Uno sin uso se elimina como antes.
        $unused = CheckListItem::create(['name' => 'Limpiaparabrisas', 'type' => 'boolean', 'required' => false]);
        $this->deleteJson("/api/checklists/{$unused->id}")->assertOk();
    }

    public function test_a_vehicle_with_receptions_is_not_deleted(): void
    {
        $this->createReception();

        $this->deleteJson("/api/vehicles/{$this->vehicle->id}")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'VEHICLE_IN_USE');
        $this->assertSame('QAR123', $this->vehicle->fresh()->plate);

        $spare = $this->makeVehicle($this->client, 'SIN123');
        $this->deleteJson("/api/vehicles/{$spare->id}")->assertOk();
        $this->assertSoftDeleted($spare);
    }
}
