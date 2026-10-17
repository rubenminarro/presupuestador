<?php

namespace Tests\Feature\MasterData;

use Tests\Feature\Parts\PartsTestCase;

class CheckListItemTest extends PartsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
    }

    public function test_crud_of_checklist_items(): void
    {
        $item = $this->postJson('/api/checklists', ['name' => 'Nivel de aceite', 'type' => 'boolean', 'required' => true])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Nivel de aceite')
            ->assertJsonPath('data.required', true)
            ->json('data');

        $this->getJson('/api/checklists?search=aceite')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/checklists/{$item['id']}")->assertOk();

        $this->patchJson("/api/checklists/{$item['id']}", ['name' => 'Nivel de aceite del motor', 'type' => 'text', 'required' => false])
            ->assertOk()
            ->assertJsonPath('data.required', false);

        $this->deleteJson("/api/checklists/{$item['id']}")->assertOk();
        $this->getJson("/api/checklists/{$item['id']}")->assertNotFound()->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');
    }

    public function test_checklist_item_validation(): void
    {
        $this->postJson('/api/checklists', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type', 'required']);

        $this->postJson('/api/checklists', ['name' => 'Luces', 'type' => 'color', 'required' => true])->assertJsonValidationErrors(['type']);
        $this->postJson('/api/checklists', ['name' => 'Luces 123', 'type' => 'boolean', 'required' => true])->assertJsonValidationErrors(['name']);

        $this->postJson('/api/checklists', ['name' => 'Luces', 'type' => 'boolean', 'required' => true])->assertCreated();
        $this->postJson('/api/checklists', ['name' => 'Luces', 'type' => 'boolean', 'required' => true])->assertJsonValidationErrors(['name']);
    }

    public function test_mechanic_cannot_manage_checklist_items(): void
    {
        $this->actingAsMechanic();

        $this->getJson('/api/checklists')->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
    }
}
