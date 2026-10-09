<?php

namespace Tests\Feature\Parts;

use App\Models\Part;

class PartTest extends PartsTestCase
{
    public function test_admin_can_create_part_with_generated_code(): void
    {
        $this->actingAsAdmin();

        $category = $this->createCategory();

        $response = $this->postJson('/api/parts', $this->partPayload($category));

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status.value', 'active')
            ->assertJsonPath('data.unit.value', 'unit')
            ->assertJsonPath('data.category.id', $category->id);

        $code = $response->json('data.code');

        $this->assertMatchesRegularExpression('/^REP-\d{6}$/', $code);
        $this->assertDatabaseHas('parts', ['code' => $code]);
    }

    public function test_part_codes_are_not_reused_after_soft_delete(): void
    {
        $this->actingAsAdmin();

        $first = $this->createPartViaApi();

        $this->deleteJson("/api/parts/{$first['id']}")->assertOk();

        $second = $this->createPartViaApi();

        $this->assertNotSame($first['code'], $second['code']);
        $this->assertGreaterThan(
            (int) substr($first['code'], 4),
            (int) substr($second['code'], 4)
        );
        $this->assertSoftDeleted('parts', ['id' => $first['id']]);
    }

    public function test_create_part_validates_input(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/parts', ['unit' => 'barrel', 'cost_price' => -1])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['name', 'part_category_id', 'unit', 'cost_price', 'sale_price']);
    }

    public function test_cannot_create_part_in_inactive_category(): void
    {
        $this->actingAsAdmin();

        $category = $this->createCategory(['is_active' => false]);

        $this->postJson('/api/parts', $this->partPayload($category))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PART_CATEGORY_INACTIVE');
    }

    public function test_admin_can_list_filter_and_show_parts(): void
    {
        $this->actingAsAdmin();

        $filters = $this->createCategory(['name' => 'Filtros']);
        $brakes = $this->createCategory(['name' => 'Frenos']);

        $this->createPartViaApi($filters, ['name' => 'Filtro de aire']);
        $brakePart = $this->createPartViaApi($brakes, ['name' => 'Pastillas de freno']);

        $this->getJson("/api/parts?category_id={$brakes->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Pastillas de freno')
            ->assertJsonPath('meta.pagination.total', 1);

        $this->getJson('/api/parts?search=aire')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/parts/{$brakePart['id']}")
            ->assertOk()
            ->assertJsonPath('data.code', $brakePart['code'])
            ->assertJsonStructure(['data' => ['suppliers', 'category', 'description']]);
    }

    public function test_admin_can_update_part_but_not_code_or_status(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->patchJson("/api/parts/{$part['id']}", [
            'sale_price' => 60000,
            'code' => 'HACK-1',
            'status' => 'discontinued',
        ])
            ->assertOk()
            ->assertJsonPath('data.sale_price', '60000.00')
            ->assertJsonPath('data.code', $part['code'])
            ->assertJsonPath('data.status.value', 'active');
    }

    public function test_status_transitions_follow_rules(): void
    {
        $this->actingAsAdmin();

        $part = $this->createPartViaApi();

        $this->postJson("/api/parts/{$part['id']}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.status.value', 'inactive');

        $this->postJson("/api/parts/{$part['id']}/deactivate")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PART_INVALID_STATUS_TRANSITION');

        $this->postJson("/api/parts/{$part['id']}/activate")
            ->assertOk()
            ->assertJsonPath('data.status.value', 'active');

        $this->postJson("/api/parts/{$part['id']}/discontinue")
            ->assertOk()
            ->assertJsonPath('data.status.value', 'discontinued');

        $this->postJson("/api/parts/{$part['id']}/activate")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PART_DISCONTINUED');

        $this->patchJson("/api/parts/{$part['id']}", ['name' => 'Otro nombre'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PART_DISCONTINUED');
    }

    public function test_mechanic_can_read_but_not_write_parts(): void
    {
        $this->actingAsAdmin();
        $category = $this->createCategory();
        $part = $this->createPartViaApi($category);

        $this->actingAsMechanic();

        $this->getJson('/api/parts')->assertOk();
        $this->getJson("/api/parts/{$part['id']}")->assertOk();

        $this->postJson('/api/parts', $this->partPayload($category))
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->patchJson("/api/parts/{$part['id']}", ['name' => 'X'])->assertForbidden();
        $this->deleteJson("/api/parts/{$part['id']}")->assertForbidden();
        $this->postJson("/api/parts/{$part['id']}/activate")->assertForbidden();
        $this->postJson("/api/parts/{$part['id']}/deactivate")->assertForbidden();
        $this->postJson("/api/parts/{$part['id']}/discontinue")->assertForbidden();
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/parts')
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');
    }

    public function test_missing_part_returns_not_found(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/parts/999999')
            ->assertNotFound()
            ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');

        $this->assertSame(0, Part::count());
    }
}
