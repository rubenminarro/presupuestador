<?php

namespace Tests\Feature\MasterData;

use Tests\Feature\Parts\PartsTestCase;

class BrandAndVehicleModelTest extends PartsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
    }

    public function test_crud_of_brands(): void
    {
        $brand = $this->postJson('/api/brands', ['name' => 'Toyota'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Toyota')
            ->json('data');

        $this->getJson('/api/brands?search=Toy')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/brands/{$brand['id']}")->assertOk();

        $this->patchJson("/api/brands/{$brand['id']}", ['name' => 'Toyota Motors'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Toyota Motors');

        $this->deleteJson("/api/brands/{$brand['id']}")->assertOk();
        $this->getJson("/api/brands/{$brand['id']}")->assertNotFound()->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');

        // El nombre del eliminado queda libre.
        $this->postJson('/api/brands', ['name' => 'Toyota Motors'])->assertCreated();
    }

    public function test_brand_validation(): void
    {
        $this->postJson('/api/brands', [])->assertStatus(422)->assertJsonValidationErrors(['name']);
        $this->postJson('/api/brands', ['name' => 'X'])->assertJsonValidationErrors(['name']);
        $this->postJson('/api/brands', ['name' => 'Marca 123'])->assertJsonValidationErrors(['name']);

        $this->postJson('/api/brands', ['name' => 'Nissan'])->assertCreated();
        $this->postJson('/api/brands', ['name' => 'Nissan'])->assertJsonValidationErrors(['name']);
    }

    public function test_crud_of_vehicle_models(): void
    {
        $brand = $this->postJson('/api/brands', ['name' => 'Toyota'])->json('data');
        $other = $this->postJson('/api/brands', ['name' => 'Nissan'])->json('data');

        $model = $this->postJson('/api/vehicle-models', ['name' => 'Hilux SRV', 'brand_id' => $brand['id']])
            ->assertCreated()
            ->json('data');

        $this->getJson('/api/vehicle-models?search=Hilux')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/vehicle-models/{$model['id']}")->assertOk()->assertJsonPath('data.brand.id', $brand['id']);

        $this->patchJson("/api/vehicle-models/{$model['id']}", ['name' => 'Frontier', 'brand_id' => $other['id']])
            ->assertOk()
            ->assertJsonPath('data.name', 'Frontier')
            ->assertJsonPath('data.brand.id', $other['id']);

        $this->deleteJson("/api/vehicle-models/{$model['id']}")->assertOk();
        $this->getJson("/api/vehicle-models/{$model['id']}")->assertNotFound();
    }

    public function test_vehicle_model_validation(): void
    {
        $brand = $this->postJson('/api/brands', ['name' => 'Toyota'])->json('data');

        $this->postJson('/api/vehicle-models', [])->assertStatus(422)->assertJsonValidationErrors(['name', 'brand_id']);
        $this->postJson('/api/vehicle-models', ['name' => 'Hilux', 'brand_id' => 999999])->assertJsonValidationErrors(['brand_id']);
        $this->postJson('/api/vehicle-models', ['name' => 'Hilux@', 'brand_id' => $brand['id']])->assertJsonValidationErrors(['name']);

        $this->postJson('/api/vehicle-models', ['name' => 'Hilux', 'brand_id' => $brand['id']])->assertCreated();
        $this->postJson('/api/vehicle-models', ['name' => 'Hilux', 'brand_id' => $brand['id']])->assertJsonValidationErrors(['name']);
    }

    public function test_mechanic_cannot_manage_brands_or_models(): void
    {
        $this->actingAsMechanic();

        $this->getJson('/api/brands')->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
        $this->getJson('/api/vehicle-models')->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
    }
}
