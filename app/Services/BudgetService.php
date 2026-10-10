<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Part;
use App\Enums\BudgetItemType;
use App\Enums\BudgetStatus;
use App\Exceptions\BudgetException;
use App\Exceptions\PartException;
use App\Support\Decimal;

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
                'Solo se pueden enviar presupuestos en estado borrador.',
                422,
                'BUDGET_NOT_SENDABLE'
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
                'Solo se pueden aprobar presupuestos enviados.',
                422,
                'BUDGET_NOT_APPROVABLE'
            );
        }

        if (! $budget->items()->exists()) {
            throw new BudgetException(
                'No se puede aprobar un presupuesto sin items.',
                422,
                'BUDGET_WITHOUT_ITEMS'
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
                'Solo se pueden rechazar presupuestos enviados.',
                422,
                'BUDGET_NOT_REJECTABLE'
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
                'El presupuesto no puede ser cancelado en su estado actual.',
                422,
                'BUDGET_NOT_CANCELLABLE'
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
                'Solo se pueden reabrir presupuestos rechazados.',
                422,
                'BUDGET_NOT_REOPENABLE'
            );
        }

        $budget->update([
            'status' => BudgetStatus::DRAFT,
            'approved_at' => null,
        ]);

        return $budget->refresh();
    }

    /**
     * Normaliza el vínculo con el catálogo de un ítem: solo los ítems de
     * tipo repuesto llevan part_id, y el repuesto debe poder utilizarse.
     */
    public function preparePartItem(array $data, ?BudgetItem $item = null): array
    {
        $type = isset($data['type'])
            ? BudgetItemType::from($data['type'])
            : $item?->type;

        if ($type !== BudgetItemType::PART) {
            $data['part_id'] = null;

            return $data;
        }

        $partId = array_key_exists('part_id', $data) ? $data['part_id'] : $item?->part_id;

        if (!$partId) {
            return $data;
        }

        $part = Part::findOrFail($partId);

        if (!$part->isUsable()) {
            throw new PartException(
                'El repuesto está inactivo y no puede presupuestarse.',
                422,
                'PART_NOT_USABLE'
            );
        }

        $quantity = (string) ($data['quantity'] ?? $item?->quantity);

        if (!$part->unit->allowsFractions() && Decimal::hasFraction(Decimal::of($quantity, 3))) {
            throw new BudgetException(
                "El repuesto se mide en {$part->unit->label()} y no admite cantidades fraccionadas.",
                422,
                'INVENTORY_FRACTIONAL_QUANTITY_NOT_ALLOWED'
            );
        }

        return $data;
    }

    public function ensureEditable(Budget $budget): void
    {
        if ($budget->status !== BudgetStatus::DRAFT) {
            throw new BudgetException(
                'El presupuesto no puede modificarse en su estado actual.',
                422,
                'BUDGET_NOT_EDITABLE'
            );
        }
    }

    public function create(array $data, int $userId): Budget
    {
        return Budget::create([
            'reception_id' => $data['reception_id'],
            'created_by' => $userId,
            'code' => $this->generateCode(),
            'status' => BudgetStatus::DRAFT,
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function update(Budget $budget, array $data): Budget
    {
        $this->ensureEditable($budget);

        $budget->update($data);

        return $budget->refresh();
    }

    public function delete(Budget $budget): void
    {
        $this->ensureEditable($budget);

        $budget->delete();
    }

    private function generateCode(): string
    {
        $nextId = (Budget::withTrashed()->max('id') ?? 0) + 1;

        return 'BUD-' . str_pad(
            $nextId,
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}