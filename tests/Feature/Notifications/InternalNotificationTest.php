<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Invoices\InvoiceTestCase;

class InternalNotificationTest extends InvoiceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function userWithRole(string $role): User
    {
        $user = User::create([
            'name' => $role . '_' . Str::random(6),
            'first_name' => 'Test',
            'last_name' => ucfirst($role),
            'email' => Str::random(10) . '@test.local',
            'password' => bcrypt('password'),
        ]);

        return $user->assignRole($role);
    }

    private function types(User $user): array
    {
        return $user->notifications()->pluck('type')->sort()->values()->all();
    }

    public function test_budget_decisions_notify_reception_and_workshop_manager(): void
    {
        $reception = $this->userWithRole('recepcionista');
        $manager = $this->userWithRole('jefe-taller');
        $cashier = $this->userWithRole('cajero');

        $this->actingAsAdmin();

        $approved = $this->createBudgetWithItems([
            ['type' => 'labor', 'description' => 'Alineación', 'quantity' => 1, 'unit_price' => 100000],
        ]);
        $this->approveBudget($approved);

        $rejected = $this->createBudgetWithItems([
            ['type' => 'labor', 'description' => 'Balanceo', 'quantity' => 1, 'unit_price' => 50000],
        ]);
        $this->postJson("/api/budgets/{$rejected}/send")->assertOk();
        $this->postJson("/api/budgets/{$rejected}/reject")->assertOk();

        $this->assertSame(['budget_approved', 'budget_rejected'], $this->types($reception));
        $this->assertSame(['budget_approved', 'budget_rejected'], $this->types($manager));
        $this->assertSame([], $this->types($cashier));

        $data = $reception->notifications()->where('type', 'budget_approved')->sole()->data;
        $this->assertSame(['type' => 'budget', 'id' => $approved, 'code' => 'BUD-000001'], $data['subject']);
        $this->assertStringContainsString('BUD-000001', $data['message']);
    }

    public function test_completed_work_order_notifies_cashier_and_reception(): void
    {
        $cashier = $this->userWithRole('cajero');
        $stock = $this->userWithRole('deposito');

        $this->actingAsAdmin();

        $workOrder = $this->completedWorkOrder();

        $notification = $cashier->notifications()->where('type', 'work_order_completed')->sole();
        $this->assertSame($workOrder['code'], $notification->data['subject']['code']);

        // El aceite tenía stock: no hubo aviso a depósito.
        $this->assertSame([], $this->types($stock));
    }

    public function test_part_without_stock_notifies_warehouse(): void
    {
        $stock = $this->userWithRole('deposito');

        $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $workOrder = $this->workOrderWithPart($part, 2);

        $this->assertSame('pending', $workOrder['parts'][0]['status']['value']);

        $notification = $stock->notifications()->where('type', 'part_out_of_stock')->sole();
        $this->assertStringContainsString($part['sku'], $notification->data['message']);
        $this->assertSame($workOrder['id'], $notification->data['subject']['id']);
    }

    public function test_inbox_lists_and_marks_own_notifications(): void
    {
        $reception = $this->userWithRole('recepcionista');
        $other = $this->userWithRole('jefe-taller');

        $this->actingAsAdmin();

        foreach (['Alineación', 'Balanceo'] as $description) {
            $this->approveBudget($this->createBudgetWithItems([
                ['type' => 'labor', 'description' => $description, 'quantity' => 1, 'unit_price' => 1000],
            ]));
        }

        Sanctum::actingAs($reception);

        $first = $this->getJson('/api/me/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.unread_count', 2)
            ->assertJsonPath('data.0.type.value', 'budget_approved')
            ->assertJsonPath('data.0.type.label', 'Presupuesto aprobado')
            ->assertJsonPath('data.0.read_at', null)
            ->json('data.0.id');

        $this->patchJson("/api/me/notifications/{$first}/read")
            ->assertOk()
            ->assertJsonPath('data.id', $first);
        $this->assertNotNull($reception->notifications()->find($first)->read_at);

        $this->getJson('/api/me/notifications?unread=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.unread_count', 1);

        $this->postJson('/api/me/notifications/read-all')->assertOk()->assertJsonPath('data.marked_count', 1);
        $this->getJson('/api/me/notifications')->assertJsonPath('meta.unread_count', 0);

        // Avisos ajenos: 404.
        $foreign = $other->notifications()->first()->id;
        $this->patchJson("/api/me/notifications/{$foreign}/read")
            ->assertNotFound()
            ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');

        $this->getJson('/api/me/notifications?type=x')->assertStatus(422)->assertJsonValidationErrors(['type']);
    }
}
