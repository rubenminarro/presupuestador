<?php

namespace App\Services;
use App\Models\Budget;
use App\Enums\BudgetStatus;
use App\Exceptions\BudgetException;

class BudgetService
{
    public function recalculateBudget(Budget $budget): Budget
    {
        $subtotal = $budget->items()->sum('total');

        $taxRate = config('budget.tax_rate');

        $tax = $subtotal * $taxRate;

        $total = $subtotal + $tax;

        $budget->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ]);

        return $budget->refresh();
    }

    public function send(Budget $budget): Budget
    {
        if ($budget->status !== BudgetStatus::DRAFT) {
            throw new BudgetException(
                'Solo se pueden enviar presupuestos en estado borrador.'
            );
        }

        $budget->update([
            'status' => BudgetStatus::SENT,
        ]);

        return $budget->refresh();
    }

    public function approve(Budget $budget): Budget
    {
        if ($budget->status !== BudgetStatus::SENT) {
            throw new BudgetException(
                'Solo se pueden aprobar presupuestos enviados.'
            );
        }

        if (! $budget->items()->exists()) {
            throw new BudgetException(
                'No se puede aprobar un presupuesto sin items.'
            );
        }

        $this->recalculateBudget($budget);

        $budget->update([
            'status' => BudgetStatus::APPROVED,
            'approved_at' => now(),
        ]);

        return $budget->refresh();
    }

    public function reject(Budget $budget): Budget
    {
        if ($budget->status !== BudgetStatus::SENT) {
            throw new BudgetException(
                'Solo se pueden rechazar presupuestos enviados.'
            );
        }

        $budget->update([
            'status' => BudgetStatus::REJECTED,
        ]);

        return $budget->refresh();
    }

    public function cancel(Budget $budget): Budget
    {
        if (! in_array($budget->status, [
            BudgetStatus::DRAFT,
            BudgetStatus::SENT,
        ], true)) {
            throw new BudgetException(
                'El presupuesto no puede ser cancelado en su estado actual.'
            );
        }

        $budget->update([
            'status' => BudgetStatus::CANCELLED,
        ]);

        return $budget->refresh();
    }

    public function reopen(Budget $budget): Budget
    {
        if ($budget->status !== BudgetStatus::REJECTED) {
            throw new BudgetException(
                'Solo se pueden reabrir presupuestos rechazados.'
            );
        }

        $budget->update([
            'status' => BudgetStatus::DRAFT,
            'approved_at' => null,
        ]);

        return $budget->refresh();
    }

    public function ensureEditable(Budget $budget): void
    {
        if ($budget->status !== BudgetStatus::DRAFT) {
            throw new BudgetException(
                'El presupuesto no puede modificarse en su estado actual.'
            );
        }

    }
    
}