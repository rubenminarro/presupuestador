<?php

namespace App\Services;

use App\Enums\WorkOrderItemStatus;
use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Models\WorkOrderClosing;
use App\Support\Decimal;

/**
 * Cierre económico de la OT (FASE 14). Importes sin IVA.
 *
 * Venta:  mano de obra/servicios no cancelados (total presupuestado del ítem)
 *         + repuestos realmente usados (cantidad neta × unit_price, incluidos los extras)
 *         + repuestos fuera de catálogo no cancelados (total presupuestado).
 * Costo:  repuestos (cantidad neta × unit_cost) + mano de obra conocida (horas × costo)
 *         + comisiones. Si falta algún costo, el margen se calcula con los
 *         costos conocidos e is_cost_complete = false.
 */
class WorkOrderClosingService
{
    public function __construct(
        protected WorkOrderCommissionService $commissionService
    ) {
    }

    /**
     * Congela el cierre y las comisiones. Se ejecuta dentro de la transacción
     * de WorkOrderService::complete, con la OT ya completada.
     */
    public function close(WorkOrder $workOrder, ?int $closedBy): WorkOrderClosing
    {
        $workOrder->load(['items.budgetItem', 'items.mechanicAssignments.mechanic.user', 'parts']);

        $this->commissionService->freeze($workOrder);

        return $workOrder->closing()->create([
            ...$this->calculate($workOrder),
            'closed_by' => $closedBy,
            'closed_at' => $workOrder->completed_at ?? now(),
        ]);
    }

    /**
     * OT completada: el cierre congelado. OT abierta: cálculo a la fecha.
     */
    public function summary(WorkOrder $workOrder): array
    {
        $closing = $workOrder->status === WorkOrderStatus::COMPLETED
            ? $workOrder->closing
            : null;

        if ($closing) {
            return $this->present($closing->only([
                'budgeted_labor', 'budgeted_parts', 'budgeted_total',
                'sale_labor', 'sale_parts', 'sale_total',
                'cost_parts', 'cost_labor', 'commission_total', 'cost_total',
                'gross_margin', 'margin_percentage', 'is_cost_complete',
            ]), true, $closing->closed_at);
        }

        $workOrder->loadMissing(['items.budgetItem', 'items.mechanicAssignments.mechanic.user', 'parts']);

        return $this->present($this->calculate($workOrder), false, null);
    }

    private function calculate(WorkOrder $workOrder): array
    {
        $budgetedLabor = '0.00';
        $budgetedParts = '0.00';
        $saleLabor = '0.00';
        $saleParts = '0.00';
        $costParts = '0.00';
        $partsWithoutCost = 0;

        foreach ($workOrder->items as $item) {
            $itemTotal = (string) ($item->budgetItem?->total ?? '0');
            $isCancelled = $item->status === WorkOrderItemStatus::CANCELLED;

            if ($item->isCommissionable()) {
                $budgetedLabor = bcadd($budgetedLabor, $itemTotal, 2);

                if (!$isCancelled) {
                    $saleLabor = bcadd($saleLabor, $itemTotal, 2);
                }

                continue;
            }

            $budgetedParts = bcadd($budgetedParts, $itemTotal, 2);

            // Repuesto fuera de catálogo: no tiene línea de consumo; se vende
            // lo presupuestado y su costo es desconocido.
            if (!$isCancelled && $item->budgetItem?->part_id === null) {
                $saleParts = bcadd($saleParts, $itemTotal, 2);
                $partsWithoutCost++;
            }
        }

        foreach ($workOrder->parts as $line) {
            $saleParts = bcadd($saleParts, $line->totalPrice(), 2);

            $lineCost = $line->totalCost();

            if ($lineCost === null) {
                if (bccomp($line->netQuantity(), '0', 3) !== 0) {
                    $partsWithoutCost++;
                }

                continue;
            }

            $costParts = bcadd($costParts, $lineCost, 2);
        }

        $labor = $this->commissionService->laborSummary($workOrder);
        $commissionTotal = $this->commissionService->summary($workOrder)['total_commission'];

        $saleTotal = bcadd($saleLabor, $saleParts, 2);
        $costTotal = bcadd(bcadd($costParts, $labor['labor_cost'] ?? '0', 2), $commissionTotal, 2);
        $grossMargin = bcsub($saleTotal, $costTotal, 2);

        $marginPercentage = bccomp($saleTotal, '0', 2) > 0
            ? Decimal::round(bcdiv(bcmul($grossMargin, '100', 8), $saleTotal, 8), 2)
            : null;

        return [
            'budgeted_labor' => $budgetedLabor,
            'budgeted_parts' => $budgetedParts,
            'budgeted_total' => bcadd($budgetedLabor, $budgetedParts, 2),
            'sale_labor' => $saleLabor,
            'sale_parts' => $saleParts,
            'sale_total' => $saleTotal,
            'cost_parts' => $costParts,
            'cost_labor' => $labor['labor_cost'],
            'commission_total' => $commissionTotal,
            'cost_total' => $costTotal,
            'gross_margin' => $grossMargin,
            'margin_percentage' => $marginPercentage,
            'is_cost_complete' => $partsWithoutCost === 0 && $labor['is_complete'],
        ];
    }

    private function present(array $values, bool $isFinal, $closedAt): array
    {
        return [
            'is_final' => $isFinal,
            'closed_at' => $closedAt,
            'budgeted' => [
                'labor' => $values['budgeted_labor'],
                'parts' => $values['budgeted_parts'],
                'total' => $values['budgeted_total'],
            ],
            'sale' => [
                'labor' => $values['sale_labor'],
                'parts' => $values['sale_parts'],
                'total' => $values['sale_total'],
            ],
            // Venta real − presupuestado.
            'variance' => bcsub($values['sale_total'], $values['budgeted_total'], 2),
            'cost' => [
                'parts' => $values['cost_parts'],
                'labor' => $values['cost_labor'],
                'commissions' => $values['commission_total'],
                'total' => $values['cost_total'],
            ],
            'gross_margin' => $values['gross_margin'],
            'margin_percentage' => $values['margin_percentage'],
            'is_cost_complete' => (bool) $values['is_cost_complete'],
        ];
    }
}
