<?php

namespace Tests\Feature\Services;

use App\Models\ServiceCategory;
use Illuminate\Support\Str;
use Tests\Feature\WorkOrderParts\WorkOrderPartsTestCase;

class ServiceCatalogTest extends WorkOrderPartsTestCase
{
    protected function serviceCategory(string $name = 'Mecánica'): ServiceCategory
    {
        return ServiceCategory::create(['code' => Str::lower(Str::random(8)), 'name' => $name]);
    }

    protected function createService(?ServiceCategory $category = null, array $overrides = []): array
    {
        $category ??= $this->serviceCategory();

        return $this->postJson('/api/services', [
            'service_category_id' => $category->id,
            'name' => 'Alineación y balanceo',
            'standard_price' => 120000,
            ...$overrides,
        ])->assertCreated()->json('data');
    }

    public function test_admin_can_create_list_and_show_services(): void
    {
        $this->actingAsAdmin();

        $mechanical = $this->serviceCategory('Mecánica');
        $tires = $this->serviceCategory('Neumáticos');

        $service = $this->createService($mechanical, ['description' => 'Incluye 4 ruedas (hasta R17).']);
        $this->createService($tires, ['name' => 'Rotación de neumáticos', 'standard_price' => 60000]);

        $this->assertMatchesRegularExpression('/^SRV-\d{6}$/', $service['code']);
        $this->assertSame('active', $service['status']['value']);
        $this->assertSame('120000.00', $service['standard_price']);

        $this->getJson("/api/services?category_id={$tires->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Rotación de neumáticos');

        $this->getJson('/api/services?search=Alinea')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/services/{$service['id']}")
            ->assertOk()
            ->assertJsonPath('data.description', 'Incluye 4 ruedas (hasta R17).')
            ->assertJsonPath('data.category.id', $mechanical->id);

        $this->getJson('/api/service-categories')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Mecánica', 'services_count' => 1]);
    }

    public function test_service_validation(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/services', ['name' => 'Servicio @promo', 'standard_price' => -1])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['service_category_id', 'name', 'standard_price']);
    }

    public function test_service_can_be_updated_partially(): void
    {
        $this->actingAsAdmin();

        $service = $this->createService();

        $this->patchJson("/api/services/{$service['id']}", ['standard_price' => 135000])
            ->assertOk()
            ->assertJsonPath('data.standard_price', '135000.00')
            ->assertJsonPath('data.name', 'Alineación y balanceo');
    }

    public function test_status_transitions(): void
    {
        $this->actingAsAdmin();

        $service = $this->createService();

        $this->postJson("/api/services/{$service['id']}/activate")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'SERVICE_INVALID_STATUS_TRANSITION');

        $this->postJson("/api/services/{$service['id']}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.status.value', 'inactive');

        $this->postJson("/api/services/{$service['id']}/activate")
            ->assertOk()
            ->assertJsonPath('data.status.value', 'active');
    }

    public function test_service_in_use_cannot_be_deleted(): void
    {
        $this->actingAsAdmin();

        $unused = $this->createService(null, ['name' => 'Servicio sin uso']);
        $used = $this->createService();

        $this->createBudgetWithItems([
            ['type' => 'service', 'service_id' => $used['id'], 'description' => 'Alineación', 'quantity' => 1],
        ]);

        $this->deleteJson("/api/services/{$used['id']}")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'SERVICE_IN_USE');

        $this->deleteJson("/api/services/{$unused['id']}")->assertOk();
        $this->assertSoftDeleted('services', ['id' => $unused['id']]);
    }

    public function test_budget_service_item_uses_catalog_price_by_default(): void
    {
        $this->actingAsAdmin();

        $service = $this->createService(null, ['standard_price' => 120000]);

        $budgetId = $this->createBudgetWithItems([]);
        $url = "/api/budgets/{$budgetId}/items";

        $this->postJson($url, [
            'type' => 'service',
            'service_id' => $service['id'],
            'description' => 'Alineación',
            'quantity' => 2,
        ])
            ->assertCreated()
            ->assertJsonPath('data.service_id', $service['id'])
            ->assertJsonPath('data.service.code', $service['code'])
            ->assertJsonPath('data.unit_price', '120000.00')
            ->assertJsonPath('data.total', '240000.00');

        $this->postJson($url, [
            'type' => 'service',
            'service_id' => $service['id'],
            'description' => 'Alineación con descuento',
            'quantity' => 1,
            'unit_price' => 100000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.unit_price', '100000.00');

        // Sin servicio vinculado el precio sigue siendo obligatorio.
        $this->postJson($url, ['type' => 'service', 'description' => 'Trabajo especial', 'quantity' => 1])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['unit_price']);
    }

    public function test_budget_service_item_rules(): void
    {
        $this->actingAsAdmin();

        $service = $this->createService();
        $inactive = $this->createService(null, ['name' => 'Servicio inactivo']);
        $this->postJson("/api/services/{$inactive['id']}/deactivate")->assertOk();

        $budgetId = $this->createBudgetWithItems([]);
        $url = "/api/budgets/{$budgetId}/items";

        $this->postJson($url, [
            'type' => 'labor',
            'service_id' => $service['id'],
            'description' => 'Mano de obra',
            'quantity' => 1,
            'unit_price' => 100,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['service_id']);

        $this->postJson($url, [
            'type' => 'service',
            'service_id' => $inactive['id'],
            'description' => 'Servicio',
            'quantity' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'SERVICE_NOT_USABLE');

        $item = $this->postJson($url, [
            'type' => 'service',
            'service_id' => $service['id'],
            'description' => 'Alineación',
            'quantity' => 1,
        ])->assertCreated()->json('data');

        $this->patchJson("{$url}/{$item['id']}", ['type' => 'labor'])
            ->assertOk()
            ->assertJsonPath('data.service_id', null);
    }

    public function test_mechanic_can_only_read_the_catalog(): void
    {
        $this->actingAsAdmin();

        $category = $this->serviceCategory();
        $service = $this->createService($category);

        $this->actingAsMechanic();

        $this->getJson('/api/services')->assertOk();
        $this->getJson("/api/services/{$service['id']}")->assertOk();
        $this->getJson('/api/service-categories')->assertOk();

        $this->postJson('/api/services', [
            'service_category_id' => $category->id,
            'name' => 'Otro',
            'standard_price' => 1,
        ])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');
        $this->patchJson("/api/services/{$service['id']}", ['standard_price' => 1])->assertForbidden();
        $this->postJson("/api/services/{$service['id']}/deactivate")->assertForbidden();
        $this->deleteJson("/api/services/{$service['id']}")->assertForbidden();
    }
}
