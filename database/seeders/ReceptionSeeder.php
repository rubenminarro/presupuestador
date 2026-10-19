<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reception;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\ReceptionCheckList;
use App\Enums\FuelLevel;
use App\Services\ReceptionChecklistService;
use Illuminate\Support\Facades\DB;

class ReceptionSeeder extends Seeder
{
    public function run(): void
    {

        $checklistService = app(ReceptionChecklistService::class);

        $adminId = User::where('email', 'admin@mail.com.py')->value('id') ?? 1;

        $data = [
            [
                'plate' => 'AAA111',
                'service_categories' => ['general', 'mechanical'],
                'reception_date' => now()->subDays(10),
                'estimated_delivery_date' => now()->addDays(2),
                'mileage' => 125000,
                'fuel_level' => FuelLevel::HALF,
                'problem_description' => 'Ruido en la suspensión delantera.',
                'observations' => 'Cliente solicita revisión completa.',
            ],
            [
                'plate' => 'BBB222',
                'service_categories' => ['general', 'bodywork'],
                'reception_date' => now()->subDays(8),
                'estimated_delivery_date' => now()->addDays(5),
                'mileage' => 89000,
                'fuel_level' => FuelLevel::QUARTER,
                'problem_description' => 'Golpe en puerta trasera izquierda.',
                'observations' => 'Requiere presupuesto para pintura.',
            ],
            [
                'plate' => 'CCC333',
                'service_categories' => ['electrical'],
                'reception_date' => now()->subDays(7),
                'estimated_delivery_date' => now()->addDays(7),
                'mileage' => 156000,
                'fuel_level' => FuelLevel::FULL,
                'problem_description' => 'Cambio de amortiguadores y reparación de paragolpes.',
                'observations' => null,
            ],
            [
                'plate' => 'DDD444',
                'service_categories' => ['bodywork', 'electrical'],
                'reception_date' => now()->subDays(6),
                'estimated_delivery_date' => now()->addDays(3),
                'mileage' => 74000,
                'fuel_level' => FuelLevel::THREE_QUARTERS,
                'problem_description' => 'Falla en sistema de frenos.',
                'observations' => 'Vehículo llegó en grúa.',
            ],
            [
                'plate' => 'EEE555',
                'service_categories' => ['general', 'tires'],
                'reception_date' => now()->subDays(5),
                'estimated_delivery_date' => now()->addDays(10),
                'mileage' => 45000,
                'fuel_level' => FuelLevel::HALF,
                'problem_description' => 'Daño en capó y guardabarros.',
                'observations' => null,
            ],
        ];

        foreach ($data as $receptionData) {
            $vehicle = Vehicle::where('plate', $receptionData['plate'])->first();

            // Idempotente: un vehículo que ya tiene recepciones (incluso eliminadas) se omite.
            if (!$vehicle || Reception::withTrashed()->where('vehicle_id', $vehicle->id)->exists()) {
                continue;
            }

            DB::transaction(function () use ($receptionData, $vehicle, $adminId, $checklistService) {

                $serviceCategoryIds = ServiceCategory::whereIn('code', $receptionData['service_categories'])
                    ->pluck('id')
                    ->all();
                unset($receptionData['plate'], $receptionData['service_categories']);

                $receptionData['client_id'] = $vehicle->client_id;
                $receptionData['vehicle_id'] = $vehicle->id;
                $receptionData['created_by'] = $adminId;

                $reception = Reception::create($receptionData);

                $reception->serviceCategories()->sync($serviceCategoryIds);

                $checkList = ReceptionCheckList::create([
                    'reception_id' => $reception->id,
                ]);

                $checklistService->generateChecklistItems($checkList, $serviceCategoryIds);
            });
        }
    }
}
