<?php

namespace Database\Seeders;

use App\Models\MaintenancePlan;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Planes de mantenimiento de ejemplo, vinculados a servicios del catálogo
 * (ServiceSeeder) para que se registren solos al completar la OT.
 */
class MaintenancePlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Cambio de aceite', 'service' => 'Cambio de aceite y filtro', 'interval_km' => 5000, 'interval_days' => 180,
                'description' => 'Aceite de motor y filtro de aceite.'],
            ['name' => 'Rotacion de neumaticos', 'service' => 'Rotación de neumáticos', 'interval_km' => 10000, 'interval_days' => null,
                'description' => 'Rotación para un desgaste parejo.'],
            ['name' => 'Alineacion y balanceo', 'service' => 'Alineación y balanceo', 'interval_km' => 10000, 'interval_days' => 365,
                'description' => 'Alineación de tren delantero y balanceo de ruedas.'],
        ];

        foreach ($plans as $plan) {
            MaintenancePlan::firstOrCreate(
                ['name' => $plan['name']],
                [
                    'description' => $plan['description'],
                    'service_id' => Service::where('name', $plan['service'])->value('id'),
                    'interval_km' => $plan['interval_km'],
                    'interval_days' => $plan['interval_days'],
                    'is_active' => true,
                ]
            );
        }
    }
}
