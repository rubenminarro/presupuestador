<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reception;
use App\Enums\FuelLevel;

class ReceptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'client_id' => 1,
                'vehicle_id' => 1,
                'service_category_id' => [1, 2],
                'reception_date' => now()->subDays(10),
                'estimated_delivery_date' => now()->addDays(2),
                'mileage' => 125000,
                'fuel_level' => FuelLevel::HALF,
                'problem_description' => 'Ruido en la suspensión delantera.',
                'observations' => 'Cliente solicita revisión completa.',
            ],
            [
                'client_id' => 2,
                'vehicle_id' => 2,
                'service_category_id' => [1,3],
                'reception_date' => now()->subDays(8),
                'estimated_delivery_date' => now()->addDays(5),
                'mileage' => 89000,
                'fuel_level' => FuelLevel::QUARTER,
                'problem_description' => 'Golpe en puerta trasera izquierda.',
                'observations' => 'Requiere presupuesto para pintura.',
            ],
            [
                'client_id' => 3,
                'vehicle_id' => 3,
                'service_category_id' => [4],
                'reception_date' => now()->subDays(7),
                'estimated_delivery_date' => now()->addDays(7),
                'mileage' => 156000,
                'fuel_level' => FuelLevel::FULL,
                'problem_description' => 'Cambio de amortiguadores y reparación de paragolpes.',
                'observations' => null,
            ],
            [
                'client_id' => 4,
                'vehicle_id' => 4,
                'service_category_id' => [3,4],
                'reception_date' => now()->subDays(6),
                'estimated_delivery_date' => now()->addDays(3),
                'mileage' => 74000,
                'fuel_level' => FuelLevel::THREE_QUARTERS,
                'problem_description' => 'Falla en sistema de frenos.',
                'observations' => 'Vehículo llegó en grúa.',
            ],
            [
                'client_id' => 5,
                'vehicle_id' => 5,
                'service_category_id' => [1,5],
                'reception_date' => now()->subDays(5),
                'estimated_delivery_date' => now()->addDays(10),
                'mileage' => 45000,
                'fuel_level' => FuelLevel::HALF,
                'problem_description' => 'Daño en capó y guardabarros.',
                'observations' => null,
            ],
        ];

        foreach ($data as $receptionData) {
            $categoryIds = $receptionData['service_category_id'];
            unset($receptionData['service_category_id']);

            $reception = Reception::create($receptionData);

            $reception->serviceCategories()->attach($categoryIds);
        }
    }
}
