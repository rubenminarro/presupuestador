<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Budget;
use App\Models\Reception;
use App\Models\User;
use App\Services\BudgetService;

class BudgetSeeder extends Seeder
{
    /**
     * Placa del vehículo => notas del presupuesto. BudgetItemSeeder carga sus ítems.
     */
    public const BUDGETS = [
        'AAA111' => 'Presupuesto inicial para reparación de suspensión.',
        'BBB222' => 'Trabajo de chapa y pintura aprobado por el cliente.',
        'CCC333' => 'Mantenimiento de amortiguadores.',
    ];

    public function run(): void
    {
        $budgetService = app(BudgetService::class);

        $adminId = User::where('email', 'admin@mail.com.py')->value('id') ?? 1;

        foreach (self::BUDGETS as $plate => $notes) {
            $reception = self::receptionOf($plate);

            // Idempotente: una recepción que ya tiene presupuestos (incluso eliminados) se omite.
            if (!$reception || Budget::withTrashed()->where('reception_id', $reception->id)->exists()) {
                continue;
            }

            // Por el Service: genera el código y pone la recepción "en proceso".
            $budgetService->create(['reception_id' => $reception->id, 'notes' => $notes], $adminId);
        }
    }

    /**
     * Primera recepción del vehículo de ejemplo.
     */
    public static function receptionOf(string $plate): ?Reception
    {
        return Reception::whereHas('vehicle', fn ($query) => $query->where('plate', $plate))
            ->orderBy('id')
            ->first();
    }
}
