<?php

namespace Database\Seeders;

use App\Models\PartCategory;
use Illuminate\Database\Seeder;

class PartCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Filtros' => 'Filtros de aceite, aire, combustible y habitáculo.',
            'Frenos' => 'Pastillas, discos, tambores y líquido de frenos.',
            'Lubricantes' => 'Aceites de motor, caja y diferencial.',
            'Suspensión' => 'Amortiguadores, espirales, bujes y rótulas.',
            'Motor' => 'Componentes internos y periféricos del motor.',
            'Transmisión' => 'Embragues, juntas homocinéticas y componentes de caja.',
            'Eléctrico' => 'Baterías, lámparas, sensores y componentes eléctricos.',
            'Refrigeración' => 'Radiadores, termostatos, bombas de agua y refrigerante.',
            'Neumáticos' => 'Neumáticos, cámaras y válvulas.',
        ];

        foreach ($categories as $name => $description) {
            PartCategory::firstOrCreate(
                ['name' => $name],
                ['description' => $description, 'is_active' => true]
            );
        }
    }
}
