<?php

namespace Tests\Feature\Parts;

class PartCategoryTest extends PartsTestCase
{
    public function test_admin_can_manage_categories(): void
    {
        $this->actingAsAdmin();

        $category = $this->postJson('/api/part-categories', ['name' => 'Filtros'])
            ->assertCreated()
            ->assertJsonPath('data.is_active', true)
            ->json('data');

        $this->postJson('/api/part-categories', ['name' => 'Filtros'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR');

        $this->patchJson("/api/part-categories/{$category['id']}", ['description' => 'Aceite y aire'])
            ->assertOk()
            ->assertJsonPath('data.description', 'Aceite y aire');

        $this->getJson('/api/part-categories')
            ->assertOk()
            ->assertJsonPath('data.0.parts_count', 0);

        $this->deleteJson("/api/part-categories/{$category['id']}")->assertOk();

        $this->assertDatabaseMissing('part_categories', ['id' => $category['id']]);
    }

    public function test_cannot_delete_category_with_parts(): void
    {
        $this->actingAsAdmin();

        $category = $this->createCategory();
        $part = $this->createPartViaApi($category);

        $this->deleteJson("/api/part-categories/{$category->id}")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'PART_CATEGORY_HAS_PARTS');

        $this->deleteJson("/api/parts/{$part['id']}")->assertOk();

        $this->deleteJson("/api/part-categories/{$category->id}")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'PART_CATEGORY_HAS_PARTS');
    }

    public function test_mechanic_can_only_list_categories(): void
    {
        $category = $this->createCategory();

        $this->actingAsMechanic();

        $this->getJson('/api/part-categories')->assertOk();
        $this->getJson("/api/part-categories/{$category->id}")->assertForbidden();
        $this->postJson('/api/part-categories', ['name' => 'Motor'])->assertForbidden();
        $this->deleteJson("/api/part-categories/{$category->id}")->assertForbidden();
    }
}
