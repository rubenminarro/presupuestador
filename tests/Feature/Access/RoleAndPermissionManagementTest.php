<?php

namespace Tests\Feature\Access;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Parts\PartsTestCase;

class RoleAndPermissionManagementTest extends PartsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
    }

    public function test_crud_of_roles(): void
    {
        $role = $this->postJson('/api/roles', [
            'name' => 'recepcionista',
            'description' => 'Atiende la recepcion de vehiculos',
            'guard_name' => 'api',
            'permissions' => ['clients.index', 'receptions.store'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.description', 'Atiende la recepcion de vehiculos')
            ->assertJsonCount(2, 'data.permissions')
            ->json('data');

        $this->getJson('/api/roles?search=recepcion')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/roles/{$role['id']}")->assertOk()->assertJsonPath('data.name', 'recepcionista');

        $this->patchJson("/api/roles/{$role['id']}", [
            'name' => 'recepcionista',
            'description' => 'Recepcion y clientes',
            'guard_name' => 'api',
            'permissions' => ['clients.index'],
        ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Recepcion y clientes')
            ->assertJsonCount(1, 'data.permissions');

        // Un usuario con el rol obtiene solo sus permisos.
        $user = User::create([
            'name' => 'recep_qa',
            'first_name' => 'Rita',
            'last_name' => 'Lopez',
            'email' => 'recep@test.local',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('recepcionista');
        Sanctum::actingAs($user);

        $this->getJson('/api/clients')->assertOk();
        $this->getJson('/api/parts')->assertForbidden();

        $this->actingAsAdmin();

        $this->deleteJson("/api/roles/{$role['id']}")->assertOk();
        $this->getJson("/api/roles/{$role['id']}")->assertNotFound()->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');
    }

    public function test_role_validation(): void
    {
        $this->postJson('/api/roles', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'guard_name', 'permissions']);

        $valid = ['name' => 'cajero', 'description' => 'Cobra las facturas', 'guard_name' => 'api', 'permissions' => ['payments.index']];

        $this->postJson('/api/roles', [...$valid, 'name' => 'Con Espacio'])->assertJsonValidationErrors(['name']);
        $this->postJson('/api/roles', [...$valid, 'description' => 'Corta'])->assertJsonValidationErrors(['description']);
        $this->postJson('/api/roles', [...$valid, 'guard_name' => 'otro'])->assertJsonValidationErrors(['guard_name']);
        $this->postJson('/api/roles', [...$valid, 'permissions' => ['no.existe']])->assertJsonValidationErrors(['permissions']);
        $this->postJson('/api/roles', [...$valid, 'name' => 'administrador'])->assertJsonValidationErrors(['name']);
    }

    public function test_crud_of_permissions(): void
    {
        $permission = $this->postJson('/api/permissions', ['name' => 'reports.sales', 'guard_name' => 'api'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'reports.sales')
            ->json('data');

        $this->getJson('/api/permissions?search=reports')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/permissions/{$permission['id']}")->assertOk()->assertJsonPath('data.guard_name', 'api');

        $this->patchJson("/api/permissions/{$permission['id']}", ['name' => 'reports.profit'])
            ->assertOk()
            ->assertJsonPath('data.name', 'reports.profit');

        $this->deleteJson("/api/permissions/{$permission['id']}")->assertOk();
        $this->getJson("/api/permissions/{$permission['id']}")->assertNotFound();
    }

    public function test_permission_validation(): void
    {
        $this->postJson('/api/permissions', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'guard_name']);

        $this->postJson('/api/permissions', ['name' => 'sin-formato', 'guard_name' => 'api'])->assertJsonValidationErrors(['name']);
        $this->postJson('/api/permissions', ['name' => 'clients.index', 'guard_name' => 'api'])->assertJsonValidationErrors(['name']);
        $this->postJson('/api/permissions', ['name' => 'reports.sales', 'guard_name' => 'otro'])->assertJsonValidationErrors(['guard_name']);
    }

    /**
     * El administrador pasa por Gate::before; un rol propio depende de que el
     * permiso del controller coincida con el creado por el seeder.
     */
    public function test_custom_role_permissions_match_the_seeded_names(): void
    {
        $this->postJson('/api/roles', [
            'name' => 'catalogos',
            'description' => 'Mantiene catalogos y permisos',
            'guard_name' => 'api',
            'permissions' => ['permissions.index', 'vehicle_models.index', 'brands.index'],
        ])->assertCreated();

        $user = User::create([
            'name' => 'catalogos_qa',
            'first_name' => 'Luis',
            'last_name' => 'Rojas',
            'email' => 'catalogos@test.local',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('catalogos');
        Sanctum::actingAs($user);

        $this->getJson('/api/brands')->assertOk();
        $this->getJson('/api/permissions')->assertOk();
        $this->getJson('/api/vehicle-models')->assertOk();
    }

    public function test_mechanic_cannot_manage_roles_or_permissions(): void
    {
        $this->actingAsMechanic();

        $this->getJson('/api/roles')->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
        $this->getJson('/api/permissions')->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
    }
}
