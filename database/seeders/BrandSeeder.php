<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Toyota', 'Hyundai', 'Kia', 'Chevrolet', 'Volkswagen',
            'BYD', 'Changan', 'Isuzu', 'Nissan', 'Mitsubishi',
            'Ford', 'Mercedes-Benz', 'BMW', 'GWM', 'Chery',
            'Jeep', 'JAC Motors', 'Audi', 'Fiat', 'Honda',
            'Suzuki', 'Mazda', 'Geely', 'Land Rover', 'Renault'
        ];

        // Idempotente: omite las marcas que ya existen.
        foreach ($brands as $brand) {
            Brand::firstOrCreate(['name' => $brand]);
        }
    }
}
