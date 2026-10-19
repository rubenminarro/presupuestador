<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Service;
use App\Enums\BudgetItemType;
use App\Services\BudgetService;
use Illuminate\Support\Facades\DB;

class BudgetItemSeeder extends Seeder
{
    public function run(): void
    {
        $budgetService = app(BudgetService::class);

        $budgetsPayload = [
            // Placa del vehículo (ver BudgetSeeder::BUDGETS).
            'AAA111' => [
                'target_status' => 'DRAFT',
                'items' => [
                    [
                        'type' => BudgetItemType::LABOR,
                        'description' => 'Mano de obra - Cambio de bujes y amortiguadores',
                        'quantity' => 1,
                        'unit_price' => 350000,
                        'notes' => 'Incluye alineación posterior',
                    ],
                    [
                        'type' => BudgetItemType::PART,
                        'description' => 'Juego de Bujes de Parrilla',
                        'quantity' => 2,
                        'unit_price' => 120000,
                        'notes' => 'Repuesto original',
                    ],
                ],
            ],
            'BBB222' => [
                'target_status' => 'APPROVED',
                'items' => [
                    [
                        'type' => BudgetItemType::SERVICE,
                        'description' => 'Desabollado y preparación de puerta',
                        'quantity' => 1,
                        'unit_price' => 450000,
                        'notes' => null,
                    ],
                    [
                        'type' => BudgetItemType::SERVICE,
                        'description' => 'Pintura general de puerta trasera',
                        'quantity' => 1,
                        'unit_price' => 500000,
                        'notes' => 'Pintura poliuretánica',
                    ],
                ],
            ],
            'CCC333' => [
                'target_status' => 'SENT',
                'items' => [
                    [
                        'type' => BudgetItemType::PART,
                        'description' => 'Amortiguadores Delanteros',
                        'quantity' => 2,
                        'unit_price' => 650000,
                        'notes' => 'Marca Monroe',
                    ],
                    [
                        'type' => BudgetItemType::SERVICE,
                        'description' => 'Instalación de Amortiguadores',
                        'quantity' => 1,
                        'unit_price' => 200000,
                        'notes' => null,
                    ],
                ],
            ],
        ];

        DB::transaction(function () use ($budgetsPayload, $budgetService) {
            foreach ($budgetsPayload as $plate => $payload) {
                $budget = Budget::where('reception_id', BudgetSeeder::receptionOf($plate)?->id)
                    ->orderBy('id')
                    ->first();

                // Idempotente: solo carga ítems en el presupuesto de ejemplo si sigue vacío.
                if (! $budget || $budget->items()->exists()) {
                    continue;
                }

                foreach ($payload['items'] as $item) {
                    // Los servicios de ejemplo se vinculan al catálogo por nombre.
                    $serviceId = $item['type'] === BudgetItemType::SERVICE
                        ? Service::where('name', $item['description'])->value('id')
                        : null;

                    BudgetItem::create([
                        'budget_id' => $budget->id,
                        'type' => $item['type'],
                        'service_id' => $serviceId,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => $budgetService->itemTotal($item['quantity'], $item['unit_price']),
                        'notes' => $item['notes'],
                    ]);
                }

                $budgetService->recalculateBudget($budget);

                if ($payload['target_status'] === 'SENT') {
                    $budgetService->send($budget);
                } elseif ($payload['target_status'] === 'APPROVED') {
                    $budgetService->send($budget);
                    $budgetService->approve($budget);
                }
            }
        });
    }
}