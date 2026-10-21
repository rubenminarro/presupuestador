<?php

namespace Tests\Feature\Audit;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Invoices\InvoiceTestCase;

class AuditLogTest extends InvoiceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function logsFor(string $type, int $id, ?AuditEvent $event = null)
    {
        return AuditLog::where('auditable_type', $type)
            ->where('auditable_id', $id)
            ->when($event, fn ($q) => $q->where('event', $event))
            ->orderBy('id')
            ->get();
    }

    public function test_price_change_records_old_and_new_values_with_user(): void
    {
        $admin = $this->actingAsAdmin();

        $part = $this->createPartViaApi(null, ['sale_price' => 1000]);

        $created = $this->logsFor('part', $part['id'], AuditEvent::CREATED)->sole();
        $this->assertSame($admin->id, $created->user_id);
        $this->assertNull($created->old_values);
        $this->assertEquals(1000, $created->new_values['sale_price']);

        $this->patchJson("/api/parts/{$part['id']}", ['sale_price' => 1500])->assertOk();

        $updated = $this->logsFor('part', $part['id'], AuditEvent::UPDATED)->sole();
        $this->assertEquals(['sale_price' => 1000], $updated->old_values);
        $this->assertEquals(['sale_price' => 1500], $updated->new_values);
        $this->assertSame('127.0.0.1', $updated->ip_address);

        // Sin cambios reales: no se registra nada.
        $this->patchJson("/api/parts/{$part['id']}", ['sale_price' => 1500])->assertOk();
        $this->assertCount(1, $this->logsFor('part', $part['id'], AuditEvent::UPDATED));
    }

    public function test_supplier_cost_pivot_is_audited_with_part_and_supplier(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $supplier = $this->createSupplier();

        $this->postJson("/api/parts/{$part['id']}/suppliers", ['supplier_id' => $supplier->id, 'last_cost' => 500])
            ->assertSuccessful();
        $this->patchJson("/api/parts/{$part['id']}/suppliers/{$supplier->id}", ['last_cost' => 650])->assertOk();

        $logs = AuditLog::where('auditable_type', 'part_supplier')->orderBy('id')->get();

        $this->assertSame([AuditEvent::CREATED, AuditEvent::UPDATED], $logs->pluck('event')->all());
        $this->assertEquals(
            ['part_id' => $part['id'], 'supplier_id' => $supplier->id, 'last_cost' => 500],
            $logs[1]->old_values
        );
        $this->assertEquals(
            ['part_id' => $part['id'], 'supplier_id' => $supplier->id, 'last_cost' => 650],
            $logs[1]->new_values
        );
    }

    public function test_invoice_lifecycle_and_payments_are_audited(): void
    {
        $this->actingAsAdmin();

        $invoice = $this->readyInvoice();
        $this->createStamp()->assertCreated();
        $this->postJson("/api/invoices/{$invoice['id']}/issue")->assertOk();

        $issued = $this->logsFor('invoice', $invoice['id'], AuditEvent::UPDATED)
            ->first(fn (AuditLog $log) => ($log->new_values['status'] ?? null) === 'issued');

        $this->assertNotNull($issued);
        $this->assertSame('draft', $issued->old_values['status']);
        $this->assertNotNull($issued->new_values['number']);

        $total = (int) Invoice::findOrFail($invoice['id'])->total;
        $payment = $this->postJson("/api/invoices/{$invoice['id']}/payments", ['amount' => $total, 'method' => 'cash'])
            ->assertCreated()
            ->json('data');
        $this->postJson("/api/payments/{$payment['id']}/cancel", ['reason' => 'Error'])->assertOk();

        $paymentLogs = $this->logsFor('payment', $payment['id']);
        $this->assertSame([AuditEvent::CREATED, AuditEvent::UPDATED], $paymentLogs->pluck('event')->all());
        $this->assertSame('cancelled', $paymentLogs[1]->new_values['status']);
        $this->assertSame('Error', $paymentLogs[1]->new_values['cancellation_reason']);
    }

    public function test_security_events_and_password_masking(): void
    {
        // Sin actingAs: el login usa el guard por defecto (web).
        $admin = User::create([
            'name' => 'admin_' . Str::random(6),
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => Str::random(10) . '@test.local',
            'password' => bcrypt('password'),
        ])->assignRole('administrador');

        $user = User::create([
            'name' => 'cajero_' . Str::random(6),
            'first_name' => 'Ana',
            'last_name' => 'Caja',
            'email' => 'caja@test.local',
            'password' => bcrypt('Secreta.Larga.2026'),
        ]);

        $created = $this->logsFor('user', $user->id, AuditEvent::CREATED)->sole();
        $this->assertSame('********', $created->new_values['password']);

        $user->update(['password' => bcrypt('Otra.Clave.Larga.2026')]);
        $changed = $this->logsFor('user', $user->id, AuditEvent::UPDATED)->sole();
        $this->assertSame(['password' => '********'], $changed->old_values);
        $this->assertSame(['password' => '********'], $changed->new_values);

        // Login fallido y exitoso, logout.
        $this->postJson('/api/login', ['email' => 'caja@test.local', 'password' => 'incorrecta'])->assertUnauthorized();
        $failed = AuditLog::where('event', AuditEvent::LOGIN_FAILED)->sole();
        $this->assertNull($failed->user_id);
        $this->assertSame(['email' => 'caja@test.local'], $failed->new_values);

        $token = $this->postJson('/api/login', ['email' => 'caja@test.local', 'password' => 'Otra.Clave.Larga.2026'])
            ->assertOk()
            ->json('data.token');
        $this->assertSame($user->id, AuditLog::where('event', AuditEvent::LOGIN)->sole()->user_id);

        // Cada request real llega sin la sesión ni el usuario del login en memoria.
        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertSame($user->id, AuditLog::where('event', AuditEvent::LOGOUT)->sole()->user_id);

        // Permisos de un rol y roles de un usuario (syncPermissions/syncRoles).
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($admin);

        $role = $this->postJson('/api/roles', [
            'name' => 'auditor',
            'description' => 'Consulta de clientes',
            'guard_name' => 'api',
            'permissions' => ['clients.index'],
        ])
            ->assertCreated()
            ->json('data');
        $synced = $this->logsFor('role', $role['id'], AuditEvent::PERMISSIONS_SYNCED)->sole();
        $this->assertSame(['permissions' => []], $synced->old_values);
        $this->assertSame(['permissions' => ['clients.index']], $synced->new_values);

        $this->patchJson("/api/users/{$user->id}", ['role' => 'auditor'])->assertOk();
        $roles = $this->logsFor('user', $user->id, AuditEvent::ROLES_SYNCED)->sole();
        $this->assertSame(['roles' => ['auditor']], $roles->new_values);
    }

    public function test_audit_logs_are_immutable(): void
    {
        $this->actingAsAdmin();

        $this->createPartViaApi();
        $log = AuditLog::where('auditable_type', 'part')->first();

        $this->expectExceptionMessage('Los registros de auditoría no pueden modificarse.');
        $log->update(['event' => AuditEvent::DELETED]);
    }

    public function test_query_filters_and_admin_only_access(): void
    {
        $admin = $this->actingAsAdmin();

        $part = $this->createPartViaApi();
        $this->patchJson("/api/parts/{$part['id']}", ['sale_price' => 999])->assertOk();

        $this->getJson("/api/audit-logs?auditable_type=part&auditable_id={$part['id']}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.event.value', 'updated')
            ->assertJsonPath('data.0.event.label', 'Modificado')
            ->assertJsonPath('data.0.user.id', $admin->id)
            ->assertJsonPath('data.0.new_values.sale_price', 999);

        $this->getJson("/api/audit-logs?event=created&user_id={$admin->id}&date_from=" . now()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.0.event.value', 'created');

        $id = AuditLog::where('auditable_type', 'part')->first()->id;
        $this->getJson("/api/audit-logs/{$id}")
            ->assertOk()
            ->assertJsonPath('data.user.email', $admin->email)
            ->assertJsonStructure(['data' => ['user_agent', 'old_values', 'new_values']]);

        $this->getJson('/api/audit-logs?event=x&auditable_type=Part')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['event', 'auditable_type']);

        foreach (['recepcionista', 'jefe-taller', 'cajero', 'deposito', 'mechanic'] as $role) {
            $this->actingAsRole($role);
            $this->getJson('/api/audit-logs')->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
        }

        $this->assertTrue(Part::whereKey($part['id'])->exists());
    }
}
