<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

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

    public function test_protected_routes_require_a_token(): void
    {
        $this->getJson('/api/clients')->assertUnauthorized()->assertJsonPath('error_code', 'UNAUTHENTICATED');
        $this->withToken('token-invalido')->getJson('/api/clients')->assertUnauthorized();
    }
}
