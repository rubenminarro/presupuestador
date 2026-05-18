<?php

namespace App\Services;
use App\Models\Budget;

class BudgetService
{
    public function recalculateBudget(Budget $budget): void
    {
        $subTotal = $budget->items()->sum('total');

        $tax = $subTotal * 0.10;

        $total = $subTotal + $tax;

        $budget->update([
            'subtotal' => $subTotal,
            'tax' => $tax,
            'total' => $total,
        ]);
    }
}
