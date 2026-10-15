<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\ServiceCatalogService;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(ServiceCatalogService $serviceCatalogService): void
    {
        $services = [
            ['category' => 'general', 'name' => 'Diagnóstico general', 'standard_price' => 150000, 'estimated_hours' => 1],
            ['category' => 'mechanical', 'name' => 'Cambio de aceite y filtro', 'standard_price' => 80000, 'estimated_hours' => 0.5],
            ['category' => 'mechanical', 'name' => 'Alineación y balanceo', 'standard_price' => 120000, 'estimated_hours' => 1],
            ['category' => 'mechanical', 'name' => 'Instalación de amortiguadores', 'standard_price' => 200000, 'estimated_hours' => 2],
            ['category' => 'bodywork', 'name' => 'Desabollado y preparación de puerta', 'standard_price' => 450000, 'estimated_hours' => 6],
            ['category' => 'bodywork', 'name' => 'Pintura general de puerta trasera', 'standard_price' => 500000, 'estimated_hours' => 8],
            ['category' => 'electrical', 'name' => 'Diagnóstico eléctrico', 'standard_price' => 100000, 'estimated_hours' => 1.5],
            ['category' => 'tires', 'name' => 'Rotación de neumáticos', 'standard_price' => 60000, 'estimated_hours' => 0.5],
        ];

        foreach ($services as $data) {
            if (Service::withTrashed()->where('name', $data['name'])->exists()) {
                continue;
            }

            $category = ServiceCategory::where('code', $data['category'])->first();

            if (!$category) {
                continue;
            }

            $serviceCatalogService->create([
                'service_category_id' => $category->id,
                'name' => $data['name'],
                'standard_price' => $data['standard_price'],
                'estimated_hours' => $data['estimated_hours'],
            ]);
        }
    }
}
