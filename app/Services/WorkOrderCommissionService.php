<?php

namespace App\Services;

use App\Enums\MechanicStatus;
use App\Enums\WorkOrderItemStatus;
use App\Enums\WorkOrderStatus;
use App\Exceptions\WorkOrderException;
use App\Models\Mechanic;
use App\Models\WorkOrder;
use App\Models\WorkOrderCommission;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemMechanic;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Asignación de mecánicos a los trabajos de la OT y cálculo de comisiones.
 * Con la OT abierta la comisión se calcula sobre el total del ítem (sin IVA);
 * al completarla se congela en work_order_commissions (FASE 14).
 */
class WorkOrderCommissionService
{
    /**
     * Al crear la OT: el trabajo queda 100% asignado al mecánico responsable.
     */
    public function assignResponsible(WorkOrder $workOrder, WorkOrderItem $item): void
    {
        if (!$item->isCommissionable()) {
            return;
        }

        $mechanic = Mechanic::findOrFail($workOrder->mechanic_id);

        $item->mechanicAssignments()->create([
            'mechanic_id' => $mechanic->id,
            'share_percentage' => '100.00',
            'commission_percentage' => $mechanic->commission_percentage ?? '0',
        ]);
    }

    /**
     * Reemplaza la asignación completa de un trabajo.
     *
     * @param array<int, array{mechanic_id: int, share_percentage?: numeric|null}> $assignments
     */
    public function syncItemMechanics(WorkOrder $workOrder, WorkOrderItem $item, array $assignments): WorkOrderItem
    {
        return DB::transaction(function () use ($workOrder, $item, $assignments) {

            $workOrder = WorkOrder::whereKey($workOrder->id)->lockForUpdate()->firstOrFail();
            $item->refresh();

            $this->ensureEditable($workOrder, $item);

            $shares = $this->resolveShares($assignments);

            $mechanics = Mechanic::whereIn('id', array_keys($shares))->get()->keyBy('id');

            foreach ($mechanics as $mechanic) {
                if ($mechanic->status !== MechanicStatus::ACTIVE) {
                    throw new WorkOrderException(
                        "El mecánico {$mechanic->employee_code} no está activo y no puede asignarse.",
                        422,
                        'MECHANIC_NOT_ACTIVE'
                    );
                }
            }

            $existing = $item->mechanicAssignments()->get()->keyBy('mechanic_id');

            // Quitar los que ya no están.
            $item->mechanicAssignments()
                ->whereNotIn('mechanic_id', array_keys($shares))
                ->delete();

            foreach ($shares as $mechanicId => $share) {
                if ($existing->has($mechanicId)) {
                    // Se conservan el % de comisión copiado y las horas cargadas.
                    $existing[$mechanicId]->update(['share_percentage' => $share]);
                    continue;
                }

                $item->mechanicAssignments()->create([
                    'mechanic_id' => $mechanicId,
                    'share_percentage' => $share,
                    'commission_percentage' => $mechanics[$mechanicId]->commission_percentage ?? '0',
                ]);
            }

            return $item->load(['budgetItem', 'mechanicAssignments.mechanic.user']);
        });
    }

    /**
     * Horas reales de un mecánico en un trabajo (opcional). Al cargarlas se
     * copia su costo por hora; null borra las horas y el costo.
     */
    public function logHours(
        WorkOrder $workOrder,
        WorkOrderItem $item,
        Mechanic $mechanic,
        int|float|string|null $hours
    ): WorkOrderItemMechanic {
        return DB::transaction(function () use ($workOrder, $item, $mechanic, $hours) {

            $workOrder = WorkOrder::whereKey($workOrder->id)->lockForUpdate()->firstOrFail();
            $item->refresh();

            if (!in_array($workOrder->status, [WorkOrderStatus::IN_PROGRESS, WorkOrderStatus::PAUSED], true)) {
                throw new WorkOrderException(
                    'Las horas solo pueden cargarse con la orden de trabajo en progreso o pausada.',
                    422,
                    'WORK_ORDER_ITEM_HOURS_NOT_EDITABLE'
                );
            }

            if ($item->status === WorkOrderItemStatus::CANCELLED) {
                throw new WorkOrderException(
                    'El trabajo está cancelado y no admite carga de horas.',
                    422,
                    'WORK_ORDER_ITEM_HOURS_NOT_EDITABLE'
                );
            }

            $assignment = $item->mechanicAssignments()
                ->where('mechanic_id', $mechanic->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($hours === null) {
                $assignment->update(['hours' => null, 'hour_cost' => null]);
            } else {
                // Se conserva el costo copiado en la primera carga; sin costo
                // configurado en el mecánico queda desconocido (null), no cero.
                $hourCost = $assignment->hour_cost
                    ?? (bccomp((string) ($mechanic->hour_cost ?? '0'), '0', 2) > 0 ? $mechanic->hour_cost : null);

                $assignment->update([
                    'hours' => bcadd((string) $hours, '0', 2),
                    'hour_cost' => $hourCost,
                ]);
            }

            return $assignment->load(['workOrderItem.budgetItem', 'mechanic.user']);
        });
    }

    /**
     * Horas estimadas vs. reales y costo de mano de obra de una OT
     * (trabajos no cancelados). Los valores desconocidos se informan como null.
     */
    public function laborSummary(WorkOrder $workOrder): array
    {
        $estimated = null;
        $actual = null;
        $cost = null;
        $missingHours = 0;
        $missingCost = 0;

        foreach ($workOrder->items as $item) {
            if (!$item->isCommissionable() || $item->status === WorkOrderItemStatus::CANCELLED) {
                continue;
            }

            if ($item->budgetItem?->estimated_hours !== null) {
                $estimated = bcadd($estimated ?? '0', $item->budgetItem->estimated_hours, 2);
            }

            foreach ($item->mechanicAssignments as $assignment) {
                if ($assignment->hours === null) {
                    $missingHours++;
                    continue;
                }

                $actual = bcadd($actual ?? '0', $assignment->hours, 2);

                $laborCost = $assignment->laborCost();

                if ($laborCost === null) {
                    $missingCost++;
                    continue;
                }

                $cost = bcadd($cost ?? '0', $laborCost, 2);
            }
        }

        return [
            'estimated_hours' => $estimated,
            'actual_hours' => $actual,
            'labor_cost' => $cost,
            'assignments_without_hours' => $missingHours,
            'assignments_without_cost' => $missingCost,
            'is_complete' => $missingHours === 0 && $missingCost === 0,
        ];
    }

    /**
     * Cambio de responsable con la OT pendiente: los trabajos asignados por
     * completo al responsable anterior pasan al nuevo.
     */
    public function reassignResponsible(WorkOrder $workOrder, int $previousMechanicId): void
    {
        if ((int) $workOrder->mechanic_id === $previousMechanicId) {
            return;
        }

        $newMechanic = Mechanic::findOrFail($workOrder->mechanic_id);

        $itemIds = $workOrder->items()->pluck('id');

        WorkOrderItemMechanic::whereIn('work_order_item_id', $itemIds)
            ->where('mechanic_id', $previousMechanicId)
            ->where('share_percentage', 100)
            ->get()
            ->each(function (WorkOrderItemMechanic $assignment) use ($newMechanic) {
                $alreadyAssigned = WorkOrderItemMechanic::where('work_order_item_id', $assignment->work_order_item_id)
                    ->where('mechanic_id', $newMechanic->id)
                    ->exists();

                if ($alreadyAssigned) {
                    return;
                }

                $assignment->update([
                    'mechanic_id' => $newMechanic->id,
                    'commission_percentage' => $newMechanic->commission_percentage ?? '0',
                ]);
            });
    }

    /**
     * Resumen por mecánico de una OT (trabajos no cancelados).
     * Requiere items.budgetItem e items.mechanicAssignments.mechanic.user cargados.
     */
    public function summary(WorkOrder $workOrder): array
    {
        $byMechanic = [];
        $total = '0.00';

        foreach ($workOrder->items as $item) {
            if (!$item->isCommissionable() || $item->status === WorkOrderItemStatus::CANCELLED) {
                continue;
            }

            $itemTotal = (string) ($item->budgetItem?->total ?? '0');

            foreach ($item->mechanicAssignments as $assignment) {
                $amount = $assignment->commissionAmount($itemTotal);
                $id = $assignment->mechanic_id;

                $byMechanic[$id] ??= [
                    'mechanic_id' => $id,
                    'employee_code' => $assignment->mechanic?->employee_code,
                    'full_name' => $assignment->mechanic?->fullName(),
                    'commission_amount' => '0.00',
                ];

                $byMechanic[$id]['commission_amount'] = bcadd($byMechanic[$id]['commission_amount'], $amount, 2);
                $total = bcadd($total, $amount, 2);
            }
        }

        return [
            'is_final' => $workOrder->status === WorkOrderStatus::COMPLETED,
            'total_commission' => $total,
            'by_mechanic' => array_values($byMechanic),
        ];
    }

    /**
     * Congela las comisiones al completar la OT: una fila por mecánico en cada
     * trabajo completado. Se ejecuta dentro de la transacción de complete().
     */
    public function freeze(WorkOrder $workOrder): void
    {
        $workOrder->loadMissing(['items.budgetItem', 'items.mechanicAssignments']);

        $earnedAt = $workOrder->completed_at ?? now();

        foreach ($workOrder->items as $item) {
            if (!$item->isCommissionable() || $item->status !== WorkOrderItemStatus::COMPLETED) {
                continue;
            }

            $base = (string) ($item->budgetItem?->total ?? '0');

            foreach ($item->mechanicAssignments as $assignment) {
                WorkOrderCommission::create([
                    'work_order_id' => $workOrder->id,
                    'work_order_item_id' => $item->id,
                    'mechanic_id' => $assignment->mechanic_id,
                    'base_amount' => $base,
                    'share_percentage' => $assignment->share_percentage,
                    'commission_percentage' => $assignment->commission_percentage,
                    'amount' => $assignment->commissionAmount($base),
                    'earned_at' => $earnedAt,
                ]);
            }
        }
    }

    /**
     * Comisiones congeladas de un mecánico dentro del período (por fecha de
     * cierre de la OT). $status: null (todas), 'pending' o 'settled'.
     *
     * @return array{lines: Collection, total: string, total_pending: string, total_settled: string}
     */
    public function mechanicCommissions(
        Mechanic $mechanic,
        CarbonInterface $from,
        CarbonInterface $to,
        ?string $status = null
    ): array {
        $lines = WorkOrderCommission::query()
            ->where('mechanic_id', $mechanic->id)
            ->whereBetween('earned_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($status === 'pending', fn ($query) => $query->pending())
            ->when($status === 'settled', fn ($query) => $query->settled())
            ->with(['workOrder', 'workOrderItem'])
            ->orderBy('earned_at')
            ->orderBy('id')
            ->get();

        $sum = fn ($lines) => $lines->reduce(
            fn (string $total, WorkOrderCommission $line) => bcadd($total, $line->amount, 2),
            '0.00'
        );

        return [
            'lines' => $lines,
            'total' => $sum($lines),
            'total_pending' => $sum($lines->whereNull('settled_at')),
            'total_settled' => $sum($lines->whereNotNull('settled_at')),
        ];
    }

    /**
     * Marca como liquidadas las comisiones pendientes del mecánico en el período.
     *
     * @return array{count: int, total: string, settled_at: CarbonInterface}
     */
    public function settle(Mechanic $mechanic, CarbonInterface $from, CarbonInterface $to, int $userId): array
    {
        return DB::transaction(function () use ($mechanic, $from, $to, $userId) {

            $lines = WorkOrderCommission::query()
                ->where('mechanic_id', $mechanic->id)
                ->pending()
                ->whereBetween('earned_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->lockForUpdate()
                ->get();

            if ($lines->isEmpty()) {
                throw new WorkOrderException(
                    'No hay comisiones pendientes de liquidar en el período indicado.',
                    422,
                    'MECHANIC_COMMISSIONS_NOTHING_TO_SETTLE'
                );
            }

            $settledAt = now();

            WorkOrderCommission::whereKey($lines->modelKeys())->update([
                'settled_at' => $settledAt,
                'settled_by' => $userId,
            ]);

            return [
                'count' => $lines->count(),
                'total' => $lines->reduce(
                    fn (string $total, WorkOrderCommission $line) => bcadd($total, $line->amount, 2),
                    '0.00'
                ),
                'settled_at' => $settledAt,
            ];
        });
    }

    private function ensureEditable(WorkOrder $workOrder, WorkOrderItem $item): void
    {
        if (in_array($workOrder->status, [WorkOrderStatus::COMPLETED, WorkOrderStatus::CANCELLED], true)) {
            throw new WorkOrderException(
                "Los mecánicos no pueden modificarse con la orden de trabajo {$workOrder->status->label()}.",
                422,
                'WORK_ORDER_ITEM_MECHANICS_NOT_EDITABLE'
            );
        }

        if ((int) $item->work_order_id !== (int) $workOrder->id) {
            throw new WorkOrderException(
                'El item no pertenece a la orden de trabajo indicada.',
                422,
                'WORK_ORDER_ITEM_NOT_BELONGING'
            );
        }

        if (!$item->isCommissionable()) {
            throw new WorkOrderException(
                'Solo los trabajos de mano de obra o servicio tienen mecánicos asignados.',
                422,
                'WORK_ORDER_ITEM_NOT_COMMISSIONABLE'
            );
        }

        if ($item->status === WorkOrderItemStatus::CANCELLED) {
            throw new WorkOrderException(
                'El trabajo está cancelado y no admite cambios de mecánicos.',
                422,
                'WORK_ORDER_ITEM_MECHANICS_NOT_EDITABLE'
            );
        }
    }

    /**
     * Sin porcentajes → partes iguales. Con porcentajes → todos deben
     * informarlo y sumar 100.
     *
     * @return array<int, string> mechanic_id => share
     */
    private function resolveShares(array $assignments): array
    {
        $withShare = array_filter($assignments, fn ($a) => isset($a['share_percentage']));

        if ($withShare !== [] && count($withShare) !== count($assignments)) {
            throw new WorkOrderException(
                'Informe el porcentaje de todos los mecánicos o de ninguno (reparto en partes iguales).',
                422,
                'WORK_ORDER_ITEM_SHARES_INVALID'
            );
        }

        $mechanicIds = array_map(fn ($a) => (int) $a['mechanic_id'], $assignments);

        if ($withShare === []) {
            $count = count($mechanicIds);
            $base = bcdiv('100', (string) $count, 2);
            $last = bcsub('100', bcmul($base, (string) ($count - 1), 2), 2);

            $shares = array_fill_keys($mechanicIds, $base);
            $shares[end($mechanicIds)] = $last;

            return $shares;
        }

        $shares = [];
        $sum = '0';

        foreach ($assignments as $assignment) {
            $share = bcadd((string) $assignment['share_percentage'], '0', 2);
            $shares[(int) $assignment['mechanic_id']] = $share;
            $sum = bcadd($sum, $share, 2);
        }

        if (bccomp($sum, '100', 2) !== 0) {
            throw new WorkOrderException(
                "Los porcentajes deben sumar 100 (suman {$sum}).",
                422,
                'WORK_ORDER_ITEM_SHARES_INVALID'
            );
        }

        return $shares;
    }
}
