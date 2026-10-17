<?php

namespace Tests\Feature\Access;

use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;
use Tests\Feature\Parts\PartsTestCase;

class WorkshopRolesTest extends PartsTestCase
{
    /**
     * Cada permiso asignado a un rol existe (un nombre mal escrito no se
     * asignaría y el rol perdería ese acceso sin aviso).
     */
    public function test_every_role_permission_exists(): void
    {
        foreach (RolesAndPermissionsSeeder::workshopRoles() as $name => $role) {
            $assigned = Role::findByName($name, 'api')->permissions->pluck('name')->all();

            $this->assertEqualsCanonicalizing($role['permissions'], $assigned, "Permisos inexistentes en el rol {$name}.");
        }
    }

    public function test_roles_are_listed_with_their_description(): void
    {
        $this->actingAsAdmin();

        $roles = collect($this->getJson('/api/roles?per_page=20')->assertOk()->json('data'))->keyBy('name');

        foreach (['administrador', 'mechanic', 'recepcionista', 'jefe-taller', 'cajero', 'deposito'] as $name) {
            $this->assertArrayHasKey($name, $roles->all());
        }

        $this->assertSame('Facturación, cobros y caja', $roles['cajero']['description']);
    }

    public function test_reseeding_keeps_the_roles_in_sync(): void
    {
        Role::findByName('cajero', 'api')->syncPermissions([]);

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertNotEmpty(Role::findByName('cajero', 'api')->permissions);
        $this->assertSame(1, Role::where('name', 'cajero')->count());
    }

    public function test_receptionist(): void
    {
        $this->actingAsRole('recepcionista');

        $this->getJson('/api/clients')->assertOk();
        $this->getJson('/api/receptions')->assertOk();
        $this->getJson('/api/budgets')->assertOk();
        $this->getJson('/api/invoices')->assertOk();
        $this->getJson('/api/vehicle-deliveries')->assertOk();
        $this->postJson('/api/receptions/999999/delivery', [])->assertNotFound();

        $this->getJson('/api/payments')->assertForbidden();
        $this->postJson('/api/work-orders', [])->assertForbidden();
        $this->postJson('/api/inventory/entries', [])->assertForbidden();
        $this->getJson('/api/users')->assertForbidden();
        $this->postJson('/api/vehicle-deliveries/1/cancel', ['reason' => 'x'])->assertNotFound();
    }

    public function test_workshop_manager(): void
    {
        $this->actingAsRole('jefe-taller');

        $this->getJson('/api/work-orders')->assertOk();
        $this->getJson('/api/mechanics')->assertOk();
        $this->getJson('/api/diagnostics')->assertOk();
        // Permitido: llega a la validación.
        $this->postJson('/api/work-orders', [])->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_ERROR');

        $this->getJson('/api/invoices')->assertForbidden();
        $this->getJson('/api/payments')->assertForbidden();
        $this->postJson('/api/budgets', [])->assertForbidden();
        $this->postJson('/api/inventory/entries', [])->assertForbidden();
        $this->getJson('/api/users')->assertForbidden();
    }

    public function test_cashier(): void
    {
        $this->actingAsRole('cajero');

        $this->getJson('/api/invoices')->assertOk();
        $this->getJson('/api/payments')->assertOk();
        $this->getJson('/api/payments/summary')->assertOk();
        $this->getJson('/api/work-orders')->assertOk();
        $this->getJson('/api/invoice-stamps')->assertOk();

        $this->postJson('/api/invoice-stamps', [])->assertForbidden();
        $this->getJson('/api/receptions')->assertForbidden();
        $this->getJson('/api/parts')->assertForbidden();
        $this->postJson('/api/work-orders', [])->assertForbidden();
    }

    public function test_warehouse_keeper(): void
    {
        $this->actingAsRole('deposito');

        $this->getJson('/api/parts')->assertOk();
        $this->getJson('/api/suppliers')->assertOk();
        $this->getJson('/api/inventory/stocks')->assertOk();
        // Permitido: llega a la validación.
        $this->postJson('/api/inventory/entries', [])->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_ERROR');

        $this->getJson('/api/invoices')->assertForbidden();
        $this->getJson('/api/budgets')->assertForbidden();
        $this->getJson('/api/clients')->assertForbidden();
        $this->postJson('/api/work-orders', [])->assertForbidden();
    }
}
