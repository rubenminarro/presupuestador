<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reception;
use App\Enums\ServiceType;
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
                'service_type' => ServiceType::MECHANICAL,
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
                'service_type' => ServiceType::BODYWORK,
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
                'service_type' => ServiceType::MIXED,
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
                'service_type' => ServiceType::MECHANICAL,
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
                'service_type' => ServiceType::BODYWORK,
                'reception_date' => now()->subDays(5),
                'estimated_delivery_date' => now()->addDays(10),
                'mileage' => 45000,
                'fuel_level' => FuelLevel::HALF,
                'problem_description' => 'Daño en capó y guardabarros.',
                'observations' => null,
            ],

            [
                'client_id' => 1,
                'vehicle_id' => 6,
                'service_type' => ServiceType::MECHANICAL,
                'reception_date' => now()->subDays(4),
                'estimated_delivery_date' => now()->addDays(4),
                'mileage' => 98000,
                'fuel_level' => FuelLevel::FULL,
                'problem_description' => 'Cambio de embrague.',
                'observations' => 'Cliente requiere repuestos originales.',
            ],

            [
                'client_id' => 2,
                'vehicle_id' => 7,
                'service_type' => ServiceType::MIXED,
                'reception_date' => now()->subDays(3),
                'estimated_delivery_date' => now()->addDays(8),
                'mileage' => 132000,
                'fuel_level' => FuelLevel::EMPTY,
                'problem_description' => 'Reparación de motor y pintura lateral.',
                'observations' => null,
            ],

            [
                'client_id' => 3,
                'vehicle_id' => 8,
                'service_type' => ServiceType::BODYWORK,
                'reception_date' => now()->subDays(2),
                'estimated_delivery_date' => now()->addDays(6),
                'mileage' => 68000,
                'fuel_level' => FuelLevel::QUARTER,
                'problem_description' => 'Rayones en paragolpes delantero.',
                'observations' => null,
            ],

            [
                'client_id' => 4,
                'vehicle_id' => 9,
                'service_type' => ServiceType::MECHANICAL,
                'reception_date' => now()->subDay(),
                'estimated_delivery_date' => now()->addDays(3),
                'mileage' => 172000,
                'fuel_level' => FuelLevel::HALF,
                'problem_description' => 'Pérdida de potencia del motor.',
                'observations' => 'Posible falla en inyectores.',
            ],

            [
                'client_id' => 5,
                'vehicle_id' => 10,
                'service_type' => ServiceType::MIXED,
                'reception_date' => now(),
                'estimated_delivery_date' => now()->addDays(12),
                'mileage' => 115000,
                'fuel_level' => FuelLevel::FULL,
                'problem_description' => 'Cambio de suspensión y reparación de techo.',
                'observations' => 'Vehículo asegurado.',
            ],
        ];

        foreach ($data as $reception) {
            Reception::create($reception);
        }
    }
}
