<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceCategory;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
        [
            'code' => 'general',
            'name' => 'General',
        ],
        [
            'code' => 'mechanical',
            'name' => 'Mecánica',
        ],
        [
            'code' => 'bodywork',
            'name' => 'Chapería',
        ],
        [
            'code' => 'electrical',
            'name' => 'Electricidad',
        ],
        [
            'code' => 'tires',
            'name' => 'Neumáticos',
        ],
        ];

        foreach ($categories as $category) {
            ServiceCategory::updateOrCreate(
                ['code' => $category['code']],
                ['name' => $category['name']]
            );
        }

    }
}
