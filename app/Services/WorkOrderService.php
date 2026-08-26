<?php

namespace App\Services;

use App\Enums\BudgetStatus;
use App\Enums\WorkOrderItemStatus;
use App\Enums\WorkOrderItemType;
use App\Enums\WorkOrderStatus;
use App\Exceptions\WorkOrderException;
use App\Models\Budget;
use App\Models\Mechanic;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Support\Facades\DB;

class WorkOrderService
{
    public function createFromBudget(
        Budget $budget,
        Mechanic $mechanic,
        int $createdBy
    ): WorkOrder {
        
        return DB::transaction(function () use ($budget, $mechanic, $createdBy) {

            $budget->loadMissing('items');

            if ($budget->status !== BudgetStatus::APPROVED) {
                throw new WorkOrderException(
                    'El presupuesto debe estar aprobado para crear una orden de trabajo.'
                );
            }

            if ($budget->items->isEmpty()) {
                throw new WorkOrderException(
                    'El presupuesto debe tener al menos un item para crear una orden de trabajo.'
                );
            }

            if ($budget->workOrder()->exists()) {
                throw new WorkOrderException(
                    'El presupuesto ya tiene una orden de trabajo.'
                );
            }

            if (!$mechanic->exists) {
                throw new WorkOrderException(
                    'El mecánico seleccionado no existe.'
                );
            }

            $workOrder = WorkOrder::create([
                'reception_id' => $budget->reception_id,
                'budget_id' => $budget->id,
                'mechanic_id' => $mechanic->id,
                'created_by' => $createdBy,
                'code' => $this->generateCode(),
                'status' => WorkOrderStatus::PENDING,
                'started_at' => null,
                'completed_at' => null,
                'notes' => null,
            ]);

            foreach ($budget->items as $budgetItem) {
                $workOrder->items()->create([
                    'budget_item_id' => $budgetItem->id,
                    'type' => WorkOrderItemType::from(
                        $budgetItem->type->value
                    ),
                    'description' => $budgetItem->description,
                    'quantity' => $budgetItem->quantity,
                    'status' => WorkOrderItemStatus::PENDING,
                    'notes' => null,
                ]);
            }

            return $workOrder->load([
                'reception',
                'budget',
                'mechanic',
                'creator',
                'items',
            ]);
        });
    }

    public function start(WorkOrder $workOrder): WorkOrder
    {
        return DB::transaction(function () use ($workOrder) {

            $workOrder->refresh();

            if ($workOrder->status !== WorkOrderStatus::PENDING) {
                throw new WorkOrderException(
                    'La orden de trabajo solo puede iniciarse cuando está pendiente.'
                );
            }

            $workOrder->update([
                'status' => WorkOrderStatus::IN_PROGRESS,
                'started_at' => $workOrder->started_at ?? now(),
            ]);

            return $workOrder->load([
                'reception',
                'budget',
                'mechanic',
                'creator',
                'items',
            ]);
        });
    }

    public function pause(WorkOrder $workOrder): WorkOrder
    {
        return DB::transaction(function () use ($workOrder) {

            $workOrder->refresh();

            if ($workOrder->status !== WorkOrderStatus::IN_PROGRESS) {
                throw new WorkOrderException(
                    'La orden de trabajo solo puede pausarse cuando está en progreso.'
                );
            }

            $workOrder->update([
                'status' => WorkOrderStatus::PAUSED,
            ]);

            return $workOrder->load([
                'reception',
                'budget',
                'mechanic',
                'creator',
                'items',
            ]);
        });
    }

    public function resume(WorkOrder $workOrder): WorkOrder
    {
        return DB::transaction(function () use ($workOrder) {

            $workOrder->refresh();

            if ($workOrder->status !== WorkOrderStatus::PAUSED) {
                throw new WorkOrderException(
                    'La orden de trabajo solo puede reanudarse cuando está pausada.'
                );
            }

            $workOrder->update([
                'status' => WorkOrderStatus::IN_PROGRESS,
            ]);

            return $workOrder->load([
                'reception',
                'budget',
                'mechanic',
                'creator',
                'items',
            ]);
        });
    }

    public function complete(WorkOrder $workOrder): WorkOrder
    {
        return DB::transaction(function () use ($workOrder) {

            $workOrder->refresh();

            if ($workOrder->status !== WorkOrderStatus::IN_PROGRESS) {
                throw new WorkOrderException(
                    'La orden de trabajo solo puede completarse cuando está en progreso.'
                );
            }

            $items = $workOrder->items()->get();

            if ($items->isEmpty()) {
                throw new WorkOrderException(
                    'La orden de trabajo debe tener al menos un item para poder completarse.'
                );
            }

            $hasPendingItems = $items->contains(function ($item) {
                return in_array($item->status, [
                    WorkOrderItemStatus::PENDING,
                    WorkOrderItemStatus::IN_PROGRESS,
                ], true);
            });

            if ($hasPendingItems) {
                throw new WorkOrderException(
                    'La orden de trabajo no puede completarse porque tiene items pendientes.'
                );
            }

            $workOrder->update([
                'status' => WorkOrderStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            return $workOrder->load([
                'reception',
                'budget',
                'mechanic',
                'creator',
                'items',
            ]);
        });
    }

    public function cancel(WorkOrder $workOrder): WorkOrder
    {
        return DB::transaction(function () use ($workOrder) {

            $workOrder->refresh();

            if ($workOrder->status !== WorkOrderStatus::PENDING) {
                throw new WorkOrderException(
                    'La orden de trabajo solo puede cancelarse cuando está pendiente.'
                );
            }

            $workOrder->update([
                'status' => WorkOrderStatus::CANCELLED,
                'started_at' => null,
                'completed_at' => null,
            ]);

            $workOrder->items()
                ->whereIn('status', [
                    WorkOrderItemStatus::PENDING,
                    WorkOrderItemStatus::IN_PROGRESS,
                ])
                ->update([
                    'status' => WorkOrderItemStatus::CANCELLED,
                ]);

            return $workOrder->load([
                'reception',
                'budget',
                'mechanic',
                'creator',
                'items',
            ]);
        });
    }

    public function startItem(
        WorkOrder $workOrder,
        WorkOrderItem $item
    ): WorkOrderItem {
        return DB::transaction(function () use ($workOrder, $item) {

            $workOrder->refresh();
            $item->refresh();

            $this->ensureItemBelongsToWorkOrder($workOrder, $item);

            if ($workOrder->status !== WorkOrderStatus::IN_PROGRESS) {
                throw new WorkOrderException(
                    'No se puede iniciar un item si la orden de trabajo no está en progreso.'
                );
            }

            if ($item->status !== WorkOrderItemStatus::PENDING) {
                throw new WorkOrderException(
                    'El item solo puede iniciarse cuando está pendiente.'
                );
            }

            $item->update([
                'status' => WorkOrderItemStatus::IN_PROGRESS,
            ]);

            return $item->refresh();
        });
    }

    public function completeItem(
        WorkOrder $workOrder,
        WorkOrderItem $item
    ): WorkOrderItem {
        return DB::transaction(function () use ($workOrder, $item) {

            $workOrder->refresh();
            $item->refresh();

            $this->ensureItemBelongsToWorkOrder($workOrder, $item);

            if ($workOrder->status !== WorkOrderStatus::IN_PROGRESS) {
                throw new WorkOrderException(
                    'No se puede completar un item si la orden de trabajo no está en progreso.'
                );
            }

            if ($item->status !== WorkOrderItemStatus::IN_PROGRESS) {
                throw new WorkOrderException(
                    'El item solo puede completarse cuando está en progreso.'
                );
            }

            $item->update([
                'status' => WorkOrderItemStatus::COMPLETED,
            ]);

            return $item->refresh();
        });
    }

    public function cancelItem(
        WorkOrder $workOrder,
        WorkOrderItem $item
    ): WorkOrderItem {
        return DB::transaction(function () use ($workOrder, $item) {

            $workOrder->refresh();
            $item->refresh();

            $this->ensureItemBelongsToWorkOrder($workOrder, $item);

            if ($workOrder->status !== WorkOrderStatus::IN_PROGRESS) {
                throw new WorkOrderException(
                    'No se puede cancelar un item si la orden de trabajo no está en progreso.'
                );
            }

            if ($item->status !== WorkOrderItemStatus::PENDING) {
                throw new WorkOrderException(
                    'El item solo puede cancelarse cuando está pendiente.'
                );
            }

            $item->update([
                'status' => WorkOrderItemStatus::CANCELLED,
            ]);

            return $item->refresh();
        });
    }

    private function ensureItemBelongsToWorkOrder(
        WorkOrder $workOrder,
        WorkOrderItem $item
    ): void {
        if ($item->work_order_id !== $workOrder->id) {
            throw new WorkOrderException(
                'El item no pertenece a la orden de trabajo indicada.'
            );
        }
    }

    private function generateCode(): string
    {
        $nextId = (WorkOrder::withTrashed()->max('id') ?? 0) + 1;

        return 'OT-' . str_pad(
            $nextId,
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    private function ensureEditable(WorkOrder $workOrder): void
    {
        if ($workOrder->status !== WorkOrderStatus::PENDING) {
            throw new WorkOrderException(
                'La orden de trabajo solo puede editarse cuando está pendiente.'
            );
        }
    }

    public function update(WorkOrder $workOrder, array $data): WorkOrder
    {
        $this->ensureEditable($workOrder);

        $workOrder->update([
            'mechanic_id' => $data['mechanic_id'] ?? $workOrder->mechanic_id,
            'notes' => $data['notes'] ?? $workOrder->notes,
        ]);

        return $workOrder->fresh();
    }

    private function ensureItemEditable(WorkOrderItem $item): void
    {
        if ($item->status !== WorkOrderItemStatus::PENDING) {
            throw new WorkOrderException(
                'El item de la orden de trabajo solo puede editarse cuando está pendiente.'
            );
        }
    }

    public function updateItem(
        WorkOrder $workOrder,
        WorkOrderItem $item,
        array $data
    ): WorkOrderItem {
        $this->ensureItemBelongsToWorkOrder($workOrder, $item);

        $this->ensureItemEditable($item);

        $item->update([
            'description' => $data['description'] ?? $item->description,
            'quantity' => $data['quantity'] ?? $item->quantity,
            'notes' => $data['notes'] ?? $item->notes,
        ]);

        return $item->fresh();
    }
    
}