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
        $data = [
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

        foreach ($data as $serviceCategory) {
            ServiceCategory::create($serviceCategory);
        }

    }
}
