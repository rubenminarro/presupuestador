<?php

namespace App\Services;
use App\Models\Budget;

class BudgetService
{
    public function calculateTotals(Budget $budget): void
    {
        $subtotal = $budget->items()->sum('total');

        $tax = $subtotal * 0.10;

        $total = $subtotal + $tax;

        $budget->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ]);
    }
}
