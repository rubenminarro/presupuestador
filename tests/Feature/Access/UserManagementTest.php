<?php

namespace Tests\Feature\Access;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Parts\PartsTestCase;

class UserManagementTest extends PartsTestCase
{
    private const PASSWORD = 'Secreto#2026Qa';

    protected function setUp(): void
    {
        parent::setUp();

        // La regla uncompromised() consulta haveibeenpwned: se simula "no comprometida".
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);

        $this->actingAsAdmin();
    }

    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'qa_usuario',
            'email' => 'qa.usuario@gmail.com',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'role' => 'mechanic',
            'first_name' => 'Ana',
            'last_name' => 'Gomez',
            ...$overrides,
        ];
    }

    public function test_crud_of_users(): void
    {
        $user = $this->postJson('/api/users', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.email', 'qa.usuario@gmail.com')
            ->assertJsonPath('data.roles.0.name', 'mechanic')
            ->json('data');

        $this->assertNotSame(self::PASSWORD, User::find($user['id'])->password);

        $this->getJson('/api/users?search=qa.usuario')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.roles.0', 'mechanic');

        $this->getJson("/api/users/{$user['id']}")->assertOk()->assertJsonPath('data.first_name', 'Ana');

        $this->patchJson("/api/users/{$user['id']}", ['first_name' => 'Ana Maria', 'role' => 'administrador'])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Ana Maria')
            ->assertJsonPath('data.roles.0.name', 'administrador');

        $this->deleteJson("/api/users/{$user['id']}")->assertOk();

        $this->assertSoftDeleted('users', ['id' => $user['id']]);
        $this->getJson("/api/users/{$user['id']}")->assertNotFound()->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');

        // El email del eliminado se libera.
        $this->postJson('/api/users', $this->payload(['name' => 'qa_usuario2']))->assertCreated();
    }

    public function test_password_change_allows_login_with_the_new_password(): void
    {
        $user = $this->postJson('/api/users', $this->payload())->assertCreated()->json('data');

        $this->patchJson("/api/users/{$user['id']}", [
            'password' => 'Nueva#Clave2026',
            'password_confirmation' => 'Nueva#Clave2026',
        ])->assertOk();

        // El login usa el guard web (actingAs dejó sanctum como guard por defecto).
        $this->app['auth']->shouldUse('web');

        $this->postJson('/api/login', ['email' => 'qa.usuario@gmail.com', 'password' => 'Nueva#Clave2026'])->assertOk();
    }

    public function test_user_validation(): void
    {
        $this->postJson('/api/users', [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role', 'first_name', 'last_name']);

        $this->postJson('/api/users', $this->payload(['password' => 'corta', 'password_confirmation' => 'corta']))
            ->assertJsonValidationErrors(['password']);

        $this->postJson('/api/users', $this->payload(['password_confirmation' => 'Otra#Clave2026']))
            ->assertJsonValidationErrors(['password']);

        $this->postJson('/api/users', $this->payload(['role' => 'inexistente']))->assertJsonValidationErrors(['role']);
        $this->postJson('/api/users', $this->payload(['name' => 'Con Espacios']))->assertJsonValidationErrors(['name']);
        $this->postJson('/api/users', $this->payload(['first_name' => 'Ana123']))->assertJsonValidationErrors(['first_name']);

        $this->postJson('/api/users', $this->payload())->assertCreated();

        $this->postJson('/api/users', $this->payload(['name' => 'otro_usuario']))->assertJsonValidationErrors(['email']);
        $this->postJson('/api/users', $this->payload(['email' => 'otro@gmail.com']))->assertJsonValidationErrors(['name']);
    }

    public function test_mechanic_cannot_manage_users(): void
    {
        $this->actingAsMechanic();

        $this->getJson('/api/users')->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
        $this->postJson('/api/users', $this->payload())->assertForbidden();
    }
}
