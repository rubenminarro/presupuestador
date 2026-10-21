<?php

namespace Tests\Feature\MasterData;

use App\Models\User;
use Illuminate\Support\Str;
use Tests\Feature\Parts\PartsTestCase;

class MechanicTest extends PartsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
    }

    private function user(): User
    {
        return User::create([
            'name' => 'mec_' . Str::random(6),
            'first_name' => 'Pedro',
            'last_name' => 'Benitez',
            'email' => Str::random(8) . '@test.local',
            'password' => bcrypt('password'),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'user_id' => $this->user()->id,
            'employee_code' => 'MEC-QA1',
            'specialty' => 'Frenos y suspension',
            'hire_date' => '2024-03-01',
            'hour_cost' => 40000,
            'commission_percentage' => 12.5,
            'status' => 'active',
            ...$overrides,
        ];
    }

    public function test_crud_of_mechanics(): void
    {
        $mechanic = $this->postJson('/api/mechanics', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.employee_code', 'MEC-QA1')
            ->assertJsonPath('data.commission_percentage', '12.50')
            ->assertJsonPath('data.status.value', 'active')
            ->json('data');

        $this->getJson('/api/mechanics')->assertOk()->assertJsonPath('meta.pagination.total', 1);
        $this->getJson("/api/mechanics/{$mechanic['id']}")->assertOk()->assertJsonPath('data.user.full_name', 'Pedro Benitez');

        $this->patchJson("/api/mechanics/{$mechanic['id']}", ['status' => 'vacaciones'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->patchJson("/api/mechanics/{$mechanic['id']}", ['status' => 'on_leave', 'hour_cost' => 45000])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'on_leave')
            ->assertJsonPath('data.hour_cost', '45000.00');

        $this->deleteJson("/api/mechanics/{$mechanic['id']}")->assertOk();
        $this->getJson("/api/mechanics/{$mechanic['id']}")->assertNotFound()->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');
    }

    public function test_cost_and_commission_cannot_be_null(): void
    {
        // Antes: 500 por la columna NOT NULL (handoff 14, punto 10).
        $this->postJson('/api/mechanics', $this->payload(['hour_cost' => null, 'commission_percentage' => null]))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['hour_cost', 'commission_percentage']);

        // Sin enviarlos quedan en 0 (default de la columna).
        $payload = $this->payload();
        unset($payload['hour_cost'], $payload['commission_percentage']);

        $this->postJson('/api/mechanics', $payload)
            ->assertCreated()
            ->assertJsonPath('data.hour_cost', '0.00')
            ->assertJsonPath('data.commission_percentage', '0.00');
    }

    public function test_optional_fields_can_be_cleared_on_update(): void
    {
        $mechanic = $this->postJson('/api/mechanics', $this->payload(['notes' => 'Turno mañana.']))->assertCreated()->json('data');

        $this->patchJson("/api/mechanics/{$mechanic['id']}", [
            'specialty' => null,
            'hire_date' => null,
            'notes' => null,
        ])
            ->assertOk()
            ->assertJsonPath('data.specialty', null)
            ->assertJsonPath('data.hire_date', null)
            ->assertJsonPath('data.notes', null)
            ->assertJsonPath('data.hour_cost', '40000.00');

        // Costo y comisión no se vacían (la base no admite null).
        $this->patchJson("/api/mechanics/{$mechanic['id']}", ['hour_cost' => null, 'commission_percentage' => null])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['hour_cost', 'commission_percentage']);
    }

    public function test_mechanic_validation(): void
    {
        $this->postJson('/api/mechanics', [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['user_id', 'employee_code']);

        $first = $this->payload();
        $this->postJson('/api/mechanics', $first)->assertCreated();

        // Mismo usuario o mismo código de empleado.
        $this->postJson('/api/mechanics', [...$this->payload(['employee_code' => 'MEC-QA2']), 'user_id' => $first['user_id']])
            ->assertJsonValidationErrors(['user_id']);
        $this->postJson('/api/mechanics', $this->payload())->assertJsonValidationErrors(['employee_code']);

        $this->postJson('/api/mechanics', $this->payload(['employee_code' => 'MEC-QA3', 'user_id' => 999999]))->assertJsonValidationErrors(['user_id']);
        $this->postJson('/api/mechanics', $this->payload(['employee_code' => 'MEC-QA4', 'hour_cost' => -1]))->assertJsonValidationErrors(['hour_cost']);
        $this->postJson('/api/mechanics', $this->payload(['employee_code' => 'MEC-QA5', 'commission_percentage' => 150]))->assertJsonValidationErrors(['commission_percentage']);
    }

    public function test_mechanic_role_cannot_manage_mechanics(): void
    {
        $this->actingAsMechanic();

        $this->getJson('/api/mechanics')->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
        $this->postJson('/api/mechanics', $this->payload())->assertForbidden();
    }
}
