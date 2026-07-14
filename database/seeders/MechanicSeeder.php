<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Mechanic;
use App\Enums\MechanicStatus;
use Illuminate\Support\Facades\DB;

class MechanicSeeder extends Seeder
{
    public function run(): void
    {
        
        $userMecanico1 = User::where('email', 'mecanico1@mail.com.py')->first();
        $userMecanico2 = User::where('email', 'mecanico2@mail.com.py')->first();

        $mechanicsData = [
            [
                'user_id' => $userMecanico1->id,
                'employee_code' => 'MEC-001',
                'specialty' => 'Inyección Electrónica y Motor',
                'hire_date' => now()->subYears(2)->format('Y-m-d'),
                'hour_cost' => 45000.00,
                'commission_percentage' => 10.00,
                'status' => MechanicStatus::ACTIVE->value,
                'notes' => 'Especialista senior del taller.',
            ],
            [
                'user_id' => $userMecanico2->id,
                'employee_code' => 'MEC-002',
                'specialty' => 'Frenos y Suspensión',
                'hire_date' => now()->subYear()->format('Y-m-d'),
                'hour_cost' => 35000.00,
                'commission_percentage' => 8.50,
                'status' => MechanicStatus::ACTIVE->value,
                'notes' => 'Encargado del área de alineación.',
            ]
        ];

        foreach ($mechanicsData as $data) {
            DB::transaction(function () use ($data) {
                Mechanic::firstOrCreate(
                    ['user_id' => $data['user_id']],
                    $data
                );
            });
        }
    }
}
