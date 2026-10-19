<?php

namespace Tests\Feature\Seeders;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Los seeders son idempotentes: ejecutarlos de nuevo no duplica ni falla.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    private const TABLES = [
        'users', 'roles', 'permissions', 'mechanics', 'brands', 'vehicle_models', 'service_categories', 'services',
        'maintenance_plans', 'check_list_items', 'clients', 'vehicles', 'receptions', 'reception_check_lists',
        'reception_check_list_items', 'diagnostics', 'diagnostic_items', 'diagnostic_item_photos', 'budgets',
        'budget_items', 'part_categories', 'suppliers', 'parts', 'inventory_movements', 'invoice_stamps',
    ];

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return collect(self::TABLES)->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()])->all();
    }

    public function test_running_the_seeders_twice_does_not_duplicate_data(): void
    {
        Storage::fake('public');

        $this->seed(DatabaseSeeder::class);
        $first = $this->counts();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($first, $this->counts());
    }

    public function test_example_data_is_consistent(): void
    {
        Storage::fake('public');

        $this->seed(DatabaseSeeder::class);

        // Cada vehículo usa un modelo de su propia marca.
        $this->assertSame(0, DB::table('vehicles')
            ->join('vehicle_models', 'vehicle_models.id', '=', 'vehicles.vehicle_model_id')
            ->whereColumn('vehicle_models.brand_id', '!=', 'vehicles.brand_id')
            ->count());

        // Las recepciones con presupuesto quedan "en proceso" (estado derivado).
        $this->assertSame(3, DB::table('receptions')->where('status', 'in_progress')->count());
        $this->assertSame(['approved', 'draft', 'sent'], DB::table('budgets')->orderBy('status')->pluck('status')->all());
    }
}
