<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Budget;
use App\Models\User;
use App\Enums\BudgetStatus;

class BudgetSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('email', 'admin@mail.com.py')->value('id') ?? 1;

        $budgets = [
            [
                'id' => 1,
                'reception_id' => 1,
                'created_by' => $adminId,
                'code' => 'BUD-000001',
                'status' => BudgetStatus::DRAFT,
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
                'notes' => 'Presupuesto inicial para reparación de suspensión.',
                'approved_at' => null,
            ],
            [
                'id' => 2,
                'reception_id' => 2,
                'created_by' => $adminId,
                'code' => 'BUD-000002',
                'status' => BudgetStatus::DRAFT,
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
                'notes' => 'Trabajo de chapa y pintura aprobado por el cliente.',
                'approved_at' => null,
            ],
            [
                'id' => 3,
                'reception_id' => 3,
                'created_by' => $adminId,
                'code' => 'BUD-000003',
                'status' => BudgetStatus::DRAFT,
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
                'notes' => 'Mantenimiento de amortiguadores.',
                'approved_at' => null,
            ],
        ];

        foreach ($budgets as $budgetData) {
            Budget::create($budgetData);
        }
    }
}