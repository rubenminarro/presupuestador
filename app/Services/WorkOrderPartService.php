<?php

namespace App\Services;

use App\Enums\ClientDecision;
use App\Enums\WorkOrderPartStatus;
use App\Enums\WorkOrderStatus;
use App\Exceptions\InventoryException;
use App\Exceptions\PartException;
use App\Exceptions\WorkOrderPartException;
use App\Models\BudgetItem;
use App\Models\Part;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderPart;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

/**
 * Repuestos utilizados en una OT. Toda variación de stock se delega
 * en InventoryService. Orden de bloqueo: OT → línea → part_stocks.
 */
class WorkOrderPartService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected InternalNotificationService $internalNotificationService
    ) {
    }

    /**
     * Crea la línea de un ítem presupuestado con repuesto vinculado.
     * Se ejecuta dentro de la transacción de WorkOrderService::createFromBudget.
     */
    public function createFromBudgetItem(
        WorkOrder $workOrder,
        WorkOrderItem $workOrderItem,
        BudgetItem $budgetItem,
        int $userId
    ): WorkOrderPart {
        $part = Part::withTrashed()->findOrFail($budgetItem->part_id);

        $line = $workOrder->parts()->create([
            'work_order_item_id' => $workOrderItem->id,
            'part_id' => $part->id,
            'status' => WorkOrderPartStatus::PENDING,
            'quantity' => $this->inventoryService->normalizeQuantity($part, $budgetItem->quantity),
            'unit_price' => $budgetItem->unit_price,
            'added_by' => $userId,
        ]);

        $this->tryReserve($line, $part);

        return $line;
    }

    public function add(WorkOrder $workOrder, array $data, User $user): WorkOrderPart
    {
        return DB::transaction(function () use ($workOrder, $data, $user) {

            $workOrder = $this->lockWorkOrder($workOrder);

            $this->ensurePartsEditable($workOrder);

            $part = Part::findOrFail($data['part_id']);

            $this->ensurePartUsable($part);

            // El extra no reserva stock hasta que se registre la aprobación del cliente.
            $line = $workOrder->parts()->create([
                'work_order_item_id' => null,
                'part_id' => $part->id,
                'status' => WorkOrderPartStatus::AWAITING_APPROVAL,
                'quantity' => $this->inventoryService->normalizeQuantity($part, $data['quantity']),
                'unit_price' => $data['unit_price'] ?? $part->sale_price,
                'notes' => $data['notes'] ?? null,
                'added_by' => $user->id,
            ]);

            $this->internalNotificationService->extraPartAwaitingApproval($line, $part);

            return $this->loadLine($line);
        });
    }

    /**
     * Registra la aprobación del cliente de un extra e intenta reservarlo.
     */
    public function approve(WorkOrder $workOrder, WorkOrderPart $line, array $data, User $user): WorkOrderPart
    {
        return DB::transaction(function () use ($workOrder, $line, $data, $user) {

            [$workOrder, $line] = $this->lockLine($workOrder, $line);

            $this->ensurePartsEditable($workOrder);
            $this->ensureStatus($line, [WorkOrderPartStatus::AWAITING_APPROVAL], 'aprobarse');

            $line->update([
                ...$this->decisionAttributes(ClientDecision::APPROVED, $data, $user),
                'status' => WorkOrderPartStatus::PENDING,
            ]);

            $this->tryReserve($line, $line->part);

            return $this->loadLine($line);
        });
    }

    /**
     * Registra el rechazo del cliente de un extra: la línea queda cancelada.
     */
    public function reject(WorkOrder $workOrder, WorkOrderPart $line, array $data, User $user): WorkOrderPart
    {
        return DB::transaction(function () use ($workOrder, $line, $data, $user) {

            [$workOrder, $line] = $this->lockLine($workOrder, $line);

            $this->ensurePartsEditable($workOrder);
            $this->ensureStatus($line, [WorkOrderPartStatus::AWAITING_APPROVAL], 'rechazarse');

            $line->update([
                ...$this->decisionAttributes(ClientDecision::REJECTED, $data, $user),
                'status' => WorkOrderPartStatus::CANCELLED,
            ]);

            return $this->loadLine($line);
        });
    }

    public function update(WorkOrder $workOrder, WorkOrderPart $line, array $data): WorkOrderPart
    {
        return DB::transaction(function () use ($workOrder, $line, $data) {

            [$workOrder, $line] = $this->lockLine($workOrder, $line);

            $this->ensurePartsEditable($workOrder);
            $this->ensureStatus($line, WorkOrderPartStatus::openStatuses(), 'modificarse');
            $this->ensureApprovedTermsUnchanged($line, $data);

            $attributes = array_intersect_key($data, array_flip(['unit_price', 'notes']));

            if (array_key_exists('quantity', $data)) {
                $part = $line->part;
                $newQuantity = $this->inventoryService->normalizeQuantity($part, $data['quantity']);

                if ($line->status === WorkOrderPartStatus::RESERVED) {
                    $difference = bcsub($newQuantity, $line->quantity, 3);
                    $comparison = bccomp($difference, '0', 3);

                    if ($comparison > 0) {
                        $this->inventoryService->reserve($part, $difference);
                    } elseif ($comparison < 0) {
                        $this->inventoryService->release($part, ltrim($difference, '-'));
                    }
                }

                $attributes['quantity'] = $newQuantity;
            }

            $line->update($attributes);

            return $this->loadLine($line);
        });
    }

    public function reserve(WorkOrder $workOrder, WorkOrderPart $line): WorkOrderPart
    {
        return DB::transaction(function () use ($workOrder, $line) {

            [$workOrder, $line] = $this->lockLine($workOrder, $line);

            $this->ensurePartsEditable($workOrder);
            $this->ensureApproved($line);
            $this->ensureStatus($line, [WorkOrderPartStatus::PENDING], 'reservarse');

            $this->inventoryService->reserve($line->part, $line->quantity);

            $line->update(['status' => WorkOrderPartStatus::RESERVED]);

            return $this->loadLine($line);
        });
    }

    public function consume(WorkOrder $workOrder, WorkOrderPart $line, array $data, User $user): WorkOrderPart
    {
        return DB::transaction(function () use ($workOrder, $line, $data, $user) {

            [$workOrder, $line] = $this->lockLine($workOrder, $line);

            $this->ensureInProgress($workOrder, 'consumir');
            $this->ensureApproved($line);
            $this->ensureStatus($line, WorkOrderPartStatus::openStatuses(), 'consumirse');

            // En un extra no se usa más de lo que aprobó el cliente.
            if ($line->isExtra()) {
                $used = $this->inventoryService->normalizeQuantity($line->part, $data['quantity_used']);

                if (bccomp($used, $line->quantity, 3) > 0) {
                    throw new WorkOrderPartException(
                        "No se puede usar {$used}; el cliente aprobó {$line->quantity}.",
                        422,
                        'WORK_ORDER_PART_EXCEEDS_APPROVED'
                    );
                }
            }

            $reserved = $line->status === WorkOrderPartStatus::RESERVED ? $line->quantity : '0';

            $movement = $this->inventoryService->consumeReservation(
                $line->part,
                $reserved,
                $data['quantity_used'],
                $user,
                $line,
                "Consumo en OT {$workOrder->code}"
            );

            $line->update([
                'status' => WorkOrderPartStatus::CONSUMED,
                'quantity_consumed' => ltrim($movement->quantity, '-'),
                'unit_cost' => $movement->unit_cost,
                'consumed_by' => $user->id,
                'consumed_at' => $movement->created_at,
                'notes' => $data['notes'] ?? $line->notes,
            ]);

            return $this->loadLine($line);
        });
    }

    public function returnToStock(WorkOrder $workOrder, WorkOrderPart $line, array $data, User $user): WorkOrderPart
    {
        return DB::transaction(function () use ($workOrder, $line, $data, $user) {

            [$workOrder, $line] = $this->lockLine($workOrder, $line);

            $this->ensureInProgress($workOrder, 'devolver');
            $this->ensureStatus($line, [WorkOrderPartStatus::CONSUMED], 'devolverse');

            $quantity = $this->inventoryService->normalizeQuantity($line->part, $data['quantity']);
            $net = $line->netQuantity();

            if (bccomp($quantity, $net, 3) > 0) {
                throw new WorkOrderPartException(
                    "No se puede devolver {$quantity}; la cantidad utilizada neta es {$net}.",
                    422,
                    'WORK_ORDER_PART_RETURN_EXCEEDS_CONSUMED'
                );
            }

            $this->inventoryService->returnToStock(
                $line->part,
                $quantity,
                $line->unit_cost,
                $user,
                $line,
                $data['reason']
            );

            $returned = bcadd($line->quantity_returned, $quantity, 3);

            $line->update([
                'quantity_returned' => $returned,
                'status' => bccomp($returned, $line->quantity_consumed, 3) === 0
                    ? WorkOrderPartStatus::RETURNED
                    : WorkOrderPartStatus::CONSUMED,
            ]);

            return $this->loadLine($line);
        });
    }

    public function cancel(WorkOrder $workOrder, WorkOrderPart $line): WorkOrderPart
    {
        return DB::transaction(function () use ($workOrder, $line) {

            [$workOrder, $line] = $this->lockLine($workOrder, $line);

            $this->ensurePartsEditable($workOrder);
            $this->ensureStatus($line, WorkOrderPartStatus::openStatuses(), 'cancelarse');

            $this->cancelLine($line);

            return $this->loadLine($line);
        });
    }

    /**
     * Cancela las líneas sin resolver y libera sus reservas
     * (cancelación de la OT o de un ítem presupuestado).
     */
    public function cancelOpenLines(iterable $lines): void
    {
        foreach ($lines as $line) {
            $line = WorkOrderPart::whereKey($line->id)->lockForUpdate()->firstOrFail();

            if ($line->status->isOpen()) {
                $this->cancelLine($line);
            }
        }
    }

    public function ensureResolved(WorkOrder $workOrder): void
    {
        $unresolved = $workOrder->parts()->open()->count();

        if ($unresolved > 0) {
            throw new WorkOrderPartException(
                "La orden de trabajo tiene {$unresolved} repuesto(s) sin consumir ni cancelar.",
                422,
                'WORK_ORDER_HAS_UNRESOLVED_PARTS'
            );
        }
    }

    private function cancelLine(WorkOrderPart $line): void
    {
        if ($line->status === WorkOrderPartStatus::RESERVED) {
            $this->inventoryService->release($line->part, $line->quantity);
        }

        $line->update(['status' => WorkOrderPartStatus::CANCELLED]);
    }

    /**
     * Reserva si hay stock; si no alcanza, la línea queda pendiente y se
     * avisa a depósito.
     */
    private function tryReserve(WorkOrderPart $line, Part $part): void
    {
        try {
            $this->inventoryService->reserve($part, $line->quantity);
        } catch (InventoryException $e) {
            if ($e->getErrorCode() !== 'INSUFFICIENT_STOCK') {
                throw $e;
            }

            $this->internalNotificationService->partOutOfStock($line, $part);

            return;
        }

        $line->update(['status' => WorkOrderPartStatus::RESERVED]);
    }

    private function lockWorkOrder(WorkOrder $workOrder): WorkOrder
    {
        return WorkOrder::whereKey($workOrder->id)->lockForUpdate()->firstOrFail();
    }

    /**
     * @return array{0: WorkOrder, 1: WorkOrderPart}
     */
    private function lockLine(WorkOrder $workOrder, WorkOrderPart $line): array
    {
        $workOrder = $this->lockWorkOrder($workOrder);

        $line = WorkOrderPart::whereKey($line->id)->lockForUpdate()->firstOrFail();

        if ((int) $line->work_order_id !== (int) $workOrder->id) {
            throw new WorkOrderPartException(
                'El repuesto no pertenece a la orden de trabajo indicada.',
                422,
                'WORK_ORDER_PART_NOT_BELONGING'
            );
        }

        return [$workOrder, $line];
    }

    private function ensurePartsEditable(WorkOrder $workOrder): void
    {
        if (!in_array($workOrder->status, [
            WorkOrderStatus::PENDING,
            WorkOrderStatus::IN_PROGRESS,
            WorkOrderStatus::PAUSED,
        ], true)) {
            throw new WorkOrderPartException(
                "Los repuestos no pueden modificarse con la orden de trabajo {$workOrder->status->label()}.",
                422,
                'WORK_ORDER_PARTS_NOT_EDITABLE'
            );
        }
    }

    private function ensureInProgress(WorkOrder $workOrder, string $action): void
    {
        if ($workOrder->status !== WorkOrderStatus::IN_PROGRESS) {
            throw new WorkOrderPartException(
                "Solo se pueden {$action} repuestos con la orden de trabajo en progreso.",
                422,
                'WORK_ORDER_NOT_IN_PROGRESS'
            );
        }
    }

    private function ensureStatus(WorkOrderPart $line, array $allowed, string $action): void
    {
        if (!in_array($line->status, $allowed, true)) {
            throw new WorkOrderPartException(
                "El repuesto en estado {$line->status->label()} no puede {$action}.",
                422,
                'WORK_ORDER_PART_INVALID_STATUS_TRANSITION'
            );
        }
    }

    private function ensureApproved(WorkOrderPart $line): void
    {
        if ($line->status === WorkOrderPartStatus::AWAITING_APPROVAL) {
            throw new WorkOrderPartException(
                'El repuesto extra está pendiente de la aprobación del cliente.',
                422,
                'WORK_ORDER_PART_NOT_APPROVED'
            );
        }
    }

    /**
     * Lo que aprobó el cliente (cantidad y precio) no cambia; para otro
     * número se cancela el extra y se agrega uno nuevo.
     */
    private function ensureApprovedTermsUnchanged(WorkOrderPart $line, array $data): void
    {
        if ($line->client_decision !== ClientDecision::APPROVED) {
            return;
        }

        $quantityChanged = array_key_exists('quantity', $data)
            && bccomp($this->inventoryService->normalizeQuantity($line->part, $data['quantity']), $line->quantity, 3) !== 0;

        $priceChanged = array_key_exists('unit_price', $data)
            && bccomp(Decimal::of($data['unit_price'], 2), $line->unit_price, 2) !== 0;

        if ($quantityChanged || $priceChanged) {
            throw new WorkOrderPartException(
                'La cantidad y el precio del repuesto extra ya fueron aprobados por el cliente y no pueden modificarse.',
                422,
                'WORK_ORDER_PART_APPROVED_NOT_EDITABLE'
            );
        }
    }

    private function decisionAttributes(ClientDecision $decision, array $data, User $user): array
    {
        return [
            'client_decision' => $decision,
            'decided_at' => now(),
            'decided_by' => $user->id,
            'decision_channel' => $data['channel'],
            'decision_contact' => $data['contact'] ?? null,
            'decision_notes' => $data['notes'] ?? null,
        ];
    }

    private function ensurePartUsable(Part $part): void
    {
        if (!$part->isUsable()) {
            throw new PartException(
                'El repuesto está inactivo y no puede utilizarse.',
                422,
                'PART_NOT_USABLE'
            );
        }
    }

    private function loadLine(WorkOrderPart $line): WorkOrderPart
    {
        return $line->refresh()->load(['part']);
    }
}
