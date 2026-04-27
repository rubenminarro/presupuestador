<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            'Toyota', 'Hyundai', 'Kia', 'Chevrolet', 'Volkswagen', 
            'BYD', 'Changan', 'Isuzu', 'Nissan', 'Mitsubishi', 
            'Ford', 'Mercedes-Benz', 'BMW', 'GWM', 'Chery', 
            'Jeep', 'JAC Motors', 'Audi', 'Fiat', 'Honda', 
            'Suzuki', 'Mazda', 'Geely', 'Land Rover', 'Renault'
        ];

        $data = array_map(fn($brand) => [
            'name' => $brand,
            'created_at' => now(),
            'updated_at' => now(),
        ], $brands);

        Brand::insert($data);
    }
}
