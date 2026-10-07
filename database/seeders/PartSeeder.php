<?php

namespace Database\Seeders;

use App\Enums\PartUnit;
use App\Models\Part;
use App\Models\PartCategory;
use App\Models\Supplier;
use App\Services\PartService;
use Illuminate\Database\Seeder;

class PartSeeder extends Seeder
{
    public function run(PartService $partService): void
    {
        $parts = [
            ['sku' => 'FIL-ACE-001', 'name' => 'Filtro de aceite', 'brand' => 'Mann', 'category' => 'Filtros', 'unit' => PartUnit::UNIT, 'cost_price' => 35000, 'sale_price' => 50000, 'minimum_stock' => 5, 'supplier' => '80000001-1'],
            ['sku' => 'FIL-AIR-001', 'name' => 'Filtro de aire', 'brand' => 'Mann', 'category' => 'Filtros', 'unit' => PartUnit::UNIT, 'cost_price' => 45000, 'sale_price' => 65000, 'minimum_stock' => 5, 'supplier' => '80000001-1'],
            ['sku' => 'FRE-PAS-001', 'name' => 'Juego de pastillas de freno delanteras', 'brand' => 'Bosch', 'category' => 'Frenos', 'unit' => PartUnit::SET, 'cost_price' => 180000, 'sale_price' => 250000, 'minimum_stock' => 2, 'supplier' => '80000001-1'],
            ['sku' => 'LUB-5W30-001', 'name' => 'Aceite de motor 5W-30 sintético', 'brand' => 'Shell', 'category' => 'Lubricantes', 'unit' => PartUnit::LITER, 'cost_price' => 55000, 'sale_price' => 75000, 'minimum_stock' => 20, 'supplier' => '80000002-2'],
            ['sku' => 'REF-REF-001', 'name' => 'Refrigerante concentrado', 'brand' => 'Prestone', 'category' => 'Refrigeración', 'unit' => PartUnit::LITER, 'cost_price' => 40000, 'sale_price' => 58000, 'minimum_stock' => 10, 'supplier' => '80000002-2'],
        ];

        foreach ($parts as $data) {
            if (Part::withTrashed()->where('sku', $data['sku'])->exists()) {
                continue;
            }

            $category = PartCategory::where('name', $data['category'])->firstOrFail();

            $part = $partService->create([
                'sku' => $data['sku'],
                'name' => $data['name'],
                'brand' => $data['brand'],
                'part_category_id' => $category->id,
                'unit' => $data['unit'],
                'cost_price' => $data['cost_price'],
                'sale_price' => $data['sale_price'],
                'minimum_stock' => $data['minimum_stock'],
            ]);

            $supplier = Supplier::where('tax_id', $data['supplier'])->first();

            if ($supplier) {
                $partService->attachSupplier($part, $supplier, [
                    'last_cost' => $data['cost_price'],
                    'is_preferred' => true,
                ]);
            }
        }
    }
}
