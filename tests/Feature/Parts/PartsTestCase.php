<?php

namespace Tests\Feature\Parts;

use App\Models\PartCategory;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class PartsTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function actingAsRole(string $role): User
    {
        $user = User::create([
            'name' => $role . '_' . Str::random(6),
            'first_name' => 'Test',
            'last_name' => ucfirst($role),
            'email' => Str::random(10) . '@test.local',
            'password' => bcrypt('password'),
        ]);

        $user->assignRole($role);

        Sanctum::actingAs($user);

        return $user;
    }

    protected function actingAsAdmin(): User
    {
        return $this->actingAsRole('administrador');
    }

    protected function actingAsMechanic(): User
    {
        return $this->actingAsRole('mechanic');
    }

    protected function createCategory(array $attributes = []): PartCategory
    {
        return PartCategory::create([
            'name' => 'Categoría ' . Str::random(6),
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function createSupplier(array $attributes = []): Supplier
    {
        return Supplier::create([
            'name' => 'Proveedor ' . Str::random(6),
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function partPayload(PartCategory $category, array $overrides = []): array
    {
        return [
            'sku' => 'SKU-' . Str::upper(Str::random(6)),
            'name' => 'Filtro de aceite',
            'brand' => 'Mann',
            'part_category_id' => $category->id,
            'unit' => 'unit',
            'cost_price' => 35000,
            'sale_price' => 50000,
            'minimum_stock' => 1.5,
            ...$overrides,
        ];
    }

    protected function createPartViaApi(?PartCategory $category = null, array $overrides = []): array
    {
        $category ??= $this->createCategory();

        return $this->postJson('/api/parts', $this->partPayload($category, $overrides))
            ->assertCreated()
            ->json('data');
    }
}
