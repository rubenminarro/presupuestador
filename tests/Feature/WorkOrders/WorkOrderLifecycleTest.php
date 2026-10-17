<?php

namespace Tests\Feature\WorkOrders;

use Tests\Feature\WorkOrderParts\WorkOrderPartsTestCase;

class WorkOrderLifecycleTest extends WorkOrderPartsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
    }

    /**
     * OT pendiente con dos trabajos de mano de obra.
     */
    private function pendingWorkOrder(): array
    {
        $budgetId = $this->createBudgetWithItems([
            ['type' => 'labor', 'description' => 'Cambio de aceite', 'quantity' => 1, 'unit_price' => 50000],
            ['type' => 'labor', 'description' => 'Revision de frenos', 'quantity' => 1, 'unit_price' => 80000],
        ]);

        $this->approveBudget($budgetId);

        return $this->createWorkOrder($budgetId);
    }

    private function action(array $workOrder, string $action)
    {
        return $this->postJson("/api/work-orders/{$workOrder['id']}/{$action}");
    }

    private function itemAction(array $workOrder, int $index, string $action)
    {
        return $this->postJson("/api/work-orders/{$workOrder['id']}/items/{$workOrder['items'][$index]['id']}/{$action}");
    }

    /**
     * Las acciones sobre ítems devuelven la OT completa: estado del ítem indicado.
     */
    private function assertItemStatus($response, array $workOrder, int $index, string $status): void
    {
        $item = collect($response->json('data.items'))->firstWhere('id', $workOrder['items'][$index]['id']);

        $this->assertSame($status, $item['status']['value']);
    }

    public function test_creation_rules(): void
    {
        $budgetId = $this->createBudgetWithItems([['type' => 'labor', 'description' => 'Trabajo', 'quantity' => 1, 'unit_price' => 1000]]);
        $mechanic = $this->createMechanic();

        $this->postJson('/api/work-orders', ['budget_id' => $budgetId, 'mechanic_id' => $mechanic->id])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_BUDGET_NOT_APPROVED');

        $this->approveBudget($budgetId);

        $workOrder = $this->postJson('/api/work-orders', ['budget_id' => $budgetId, 'mechanic_id' => $mechanic->id])
            ->assertCreated()
            ->assertJsonPath('data.status.value', 'pending')
            ->assertJsonCount(1, 'data.items')
            ->json('data');

        $this->assertMatchesRegularExpression('/^OT-\d{6}$/', $workOrder['code']);

        $this->postJson('/api/work-orders', ['budget_id' => $budgetId, 'mechanic_id' => $mechanic->id])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_ALREADY_EXISTS');

        $this->postJson('/api/work-orders', [])->assertStatus(422)->assertJsonValidationErrors(['budget_id', 'mechanic_id']);
        $this->postJson('/api/work-orders', ['budget_id' => $budgetId, 'mechanic_id' => 999999])->assertJsonValidationErrors(['mechanic_id']);
    }

    public function test_listing_and_detail(): void
    {
        $workOrder = $this->pendingWorkOrder();
        $this->pendingWorkOrder();

        $this->getJson('/api/work-orders')->assertOk()->assertJsonPath('meta.pagination.total', 2);
        $this->getJson("/api/work-orders?code={$workOrder['code']}")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/work-orders?status=completed')->assertOk()->assertJsonCount(0, 'data');

        $this->getJson("/api/work-orders/{$workOrder['id']}")
            ->assertOk()
            ->assertJsonPath('data.code', $workOrder['code'])
            ->assertJsonCount(2, 'data.items');

        $this->getJson('/api/work-orders/999999')->assertNotFound()->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');
    }

    public function test_status_transitions(): void
    {
        $workOrder = $this->pendingWorkOrder();

        $this->action($workOrder, 'pause')->assertStatus(422)->assertJsonPath('error_code', 'WORK_ORDER_NOT_PAUSABLE');
        $this->action($workOrder, 'resume')->assertStatus(422)->assertJsonPath('error_code', 'WORK_ORDER_NOT_RESUMABLE');
        $this->action($workOrder, 'complete')->assertStatus(422)->assertJsonPath('error_code', 'WORK_ORDER_NOT_COMPLETABLE');

        $started = $this->action($workOrder, 'start')
            ->assertOk()
            ->assertJsonPath('data.status.value', 'in_progress')
            ->json('data');
        $this->assertNotNull($started['started_at']);

        $this->action($workOrder, 'start')->assertStatus(409)->assertJsonPath('error_code', 'WORK_ORDER_NOT_STARTABLE');
        $this->action($workOrder, 'cancel')->assertStatus(422)->assertJsonPath('error_code', 'WORK_ORDER_NOT_CANCELLABLE');

        $this->action($workOrder, 'pause')->assertOk()->assertJsonPath('data.status.value', 'paused');
        $this->action($workOrder, 'complete')->assertStatus(422)->assertJsonPath('error_code', 'WORK_ORDER_NOT_COMPLETABLE');
        $this->itemAction($workOrder, 0, 'start')->assertStatus(422)->assertJsonPath('error_code', 'WORK_ORDER_NOT_IN_PROGRESS');
        $this->action($workOrder, 'resume')->assertOk()->assertJsonPath('data.status.value', 'in_progress');

        $this->action($workOrder, 'complete')->assertStatus(422)->assertJsonPath('error_code', 'WORK_ORDER_HAS_PENDING_ITEMS');

        $this->completeAllItems($workOrder);

        $this->action($workOrder, 'complete')
            ->assertOk()
            ->assertJsonPath('data.status.value', 'completed');

        // Una OT completada no vuelve atrás.
        $this->action($workOrder, 'start')->assertStatus(409)->assertJsonPath('error_code', 'WORK_ORDER_NOT_STARTABLE');
        $this->action($workOrder, 'pause')->assertStatus(422)->assertJsonPath('error_code', 'WORK_ORDER_NOT_PAUSABLE');
        $this->action($workOrder, 'cancel')->assertStatus(422)->assertJsonPath('error_code', 'WORK_ORDER_NOT_CANCELLABLE');
    }

    public function test_item_transitions(): void
    {
        $workOrder = $this->pendingWorkOrder();

        $this->itemAction($workOrder, 0, 'start')->assertStatus(422)->assertJsonPath('error_code', 'WORK_ORDER_NOT_IN_PROGRESS');

        $this->action($workOrder, 'start')->assertOk();

        $this->itemAction($workOrder, 0, 'complete')->assertStatus(422)->assertJsonPath('error_code', 'WORK_ORDER_ITEM_NOT_COMPLETABLE');
        $this->assertItemStatus($this->itemAction($workOrder, 0, 'start')->assertOk(), $workOrder, 0, 'in_progress');
        $this->itemAction($workOrder, 0, 'start')->assertStatus(422)->assertJsonPath('error_code', 'WORK_ORDER_ITEM_NOT_STARTABLE');
        $this->itemAction($workOrder, 0, 'cancel')->assertStatus(422)->assertJsonPath('error_code', 'WORK_ORDER_ITEM_NOT_CANCELLABLE');
        $this->assertItemStatus($this->itemAction($workOrder, 0, 'complete')->assertOk(), $workOrder, 0, 'completed');

        // Un ítem pendiente puede cancelarse y la OT se completa igual.
        $this->assertItemStatus($this->itemAction($workOrder, 1, 'cancel')->assertOk(), $workOrder, 1, 'cancelled');
        $this->action($workOrder, 'complete')->assertOk();

        // Ítem de otra OT en la URL.
        $other = $this->pendingWorkOrder();
        $this->postJson("/api/work-orders/{$other['id']}/items/{$workOrder['items'][0]['id']}/start")
            ->assertNotFound()
            ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');
    }

    public function test_cancelling_a_pending_work_order_cancels_its_items(): void
    {
        $workOrder = $this->pendingWorkOrder();

        $cancelled = $this->action($workOrder, 'cancel')
            ->assertOk()
            ->assertJsonPath('data.status.value', 'cancelled')
            ->json('data');

        foreach ($cancelled['items'] as $item) {
            $this->assertSame('cancelled', $item['status']['value']);
        }

        $this->action($workOrder, 'start')->assertStatus(409)->assertJsonPath('error_code', 'WORK_ORDER_NOT_STARTABLE');
    }

    public function test_only_pending_work_orders_are_editable(): void
    {
        $workOrder = $this->pendingWorkOrder();
        $other = $this->createMechanic();

        $this->patchJson("/api/work-orders/{$workOrder['id']}", ['notes' => 'Cliente espera', 'mechanic_id' => $other->id])
            ->assertOk()
            ->assertJsonPath('data.notes', 'Cliente espera')
            ->assertJsonPath('data.mechanic_id', $other->id);

        // Código y estado no se modifican por edición.
        $this->patchJson("/api/work-orders/{$workOrder['id']}", ['code' => 'HACK', 'status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.code', $workOrder['code'])
            ->assertJsonPath('data.status.value', 'pending');

        $this->action($workOrder, 'start')->assertOk();

        $this->patchJson("/api/work-orders/{$workOrder['id']}", ['notes' => 'x'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WORK_ORDER_NOT_EDITABLE');
    }

    /**
     * Los ítems usan sus propios permisos (work-order-items.*), no los de la OT.
     */
    public function test_item_actions_use_the_item_permissions(): void
    {
        $workOrder = $this->pendingWorkOrder();
        $this->action($workOrder, 'start')->assertOk();

        $this->postJson('/api/roles', [
            'name' => 'ayudante',
            'description' => 'Ejecuta trabajos de la orden',
            'guard_name' => 'api',
            'permissions' => ['work-order-items.start', 'work-order-items.complete'],
        ])->assertCreated();

        $user = \App\Models\User::create([
            'name' => 'ayudante_qa',
            'first_name' => 'Hugo',
            'last_name' => 'Paez',
            'email' => 'ayudante@test.local',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('ayudante');
        \Laravel\Sanctum\Sanctum::actingAs($user);

        $this->itemAction($workOrder, 0, 'start')->assertOk();
        $this->itemAction($workOrder, 0, 'complete')->assertOk();
        $this->itemAction($workOrder, 1, 'cancel')->assertForbidden();
        $this->action($workOrder, 'pause')->assertForbidden();
    }

    public function test_mechanic_permissions_on_work_orders(): void
    {
        $workOrder = $this->pendingWorkOrder();

        $this->actingAsMechanic();

        $this->getJson('/api/work-orders')->assertOk();
        $this->getJson("/api/work-orders/{$workOrder['id']}")->assertOk();
        $this->action($workOrder, 'start')->assertOk();
        $this->itemAction($workOrder, 0, 'start')->assertOk();
        $this->itemAction($workOrder, 1, 'cancel')->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
        $this->action($workOrder, 'pause')->assertOk();
        $this->action($workOrder, 'resume')->assertOk();

        $this->patchJson("/api/work-orders/{$workOrder['id']}", ['notes' => 'x'])->assertForbidden();
        $this->postJson('/api/work-orders', ['budget_id' => 1, 'mechanic_id' => 1])->assertForbidden();
    }
}
