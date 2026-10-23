<?php

namespace Tests\Feature\Auth;

use App\Models\Mechanic;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::create([
            'name' => 'admin_test',
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin@test.local',
            'password' => bcrypt('secreto123'),
        ])->assignRole('administrador');
    }

    public function test_valid_credentials_return_token(): void
    {
        $this->postJson('/api/login', ['email' => 'admin@test.local', 'password' => 'secreto123'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_invalid_credentials_return_unauthenticated_error_code(): void
    {
        $this->postJson('/api/login', ['email' => 'admin@test.local', 'password' => 'incorrecta'])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 401)
            ->assertJsonPath('message', 'Credenciales inválidas.')
            ->assertJsonPath('error_code', 'UNAUTHENTICATED')
            ->assertJsonPath('errors.email.0', 'Email incorrecto.');
    }

    public function test_login_validation(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR');

        $this->postJson('/api/login', ['email' => 'admin', 'password' => 'x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_logout_revokes_the_token(): void
    {
        $token = $this->postJson('/api/login', ['email' => 'admin@test.local', 'password' => 'secreto123'])
            ->json('data.token');

        // Cada request real llega sin la sesión ni el usuario del login en memoria.
        $this->flushSession();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        // Sin el guard en caché, el token revocado ya no autentica.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/clients')
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');
    }

    public function test_me_returns_the_user_and_permission_names(): void
    {
        $user = User::create([
            'name' => 'cajero_test',
            'first_name' => 'Cajero',
            'last_name' => 'Test',
            'email' => 'cajero@test.local',
            'password' => bcrypt('secreto123'),
        ])->assignRole('cajero');

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'cajero@test.local')
            ->assertJsonPath('data.user.roles.0.name', 'cajero');

        $permissions = $response->json('data.permissions');

        $this->assertEqualsCanonicalizing($user->getAllPermissions()->pluck('name')->all(), $permissions);
        $this->assertContains('invoices.index', $permissions);
        $this->assertNotContains('users.index', $permissions);
        $this->assertNull($response->json('data.mechanic'));
    }

    public function test_me_returns_the_linked_mechanic(): void
    {
        $user = User::create([
            'name' => 'mecanico_test',
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'mecanico@test.local',
            'password' => bcrypt('secreto123'),
        ])->assignRole('mechanic');

        $mechanic = Mechanic::create(['user_id' => $user->id, 'employee_code' => 'EMP-001']);

        $this->actingAs($user, 'sanctum')->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.mechanic.id', $mechanic->id)
            ->assertJsonPath('data.mechanic.employee_code', 'EMP-001')
            ->assertJsonPath('data.mechanic.full_name', 'Juan Perez');
    }

    public function test_me_returns_every_permission_for_the_administrator(): void
    {
        $admin = User::where('email', 'admin@test.local')->first();

        // Gate::before autoriza al administrador aunque su rol no tenga el permiso.
        $admin->roles()->first()->revokePermissionTo('users.index');

        $permissions = $this->actingAs($admin, 'sanctum')->getJson('/api/me')
            ->assertOk()
            ->json('data.permissions');

        $this->assertEqualsCanonicalizing(
            Permission::where('guard_name', 'api')->pluck('name')->all(),
            $permissions
        );
    }

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/me')->assertUnauthorized()->assertJsonPath('error_code', 'UNAUTHENTICATED');
    }

    public function test_protected_routes_require_a_token(): void
    {
        $this->getJson('/api/clients')->assertUnauthorized()->assertJsonPath('error_code', 'UNAUTHENTICATED');
        $this->withToken('token-invalido')->getJson('/api/clients')->assertUnauthorized();
    }
}
