<?php

namespace App\Services;

use App\Enums\BudgetStatus;
use App\Enums\InternalNotificationType;
use App\Models\Budget;
use App\Models\Part;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use App\Notifications\WorkshopNotification;
use App\Support\Decimal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

/**
 * Avisos de la bandeja interna (FASE 23). Los destinatarios son los usuarios
 * de los roles configurados en config/notifications.php.
 */
class InternalNotificationService
{
    public function budgetDecided(Budget $budget): void
    {
        $budget->loadMissing('reception.vehicle');

        $approved = $budget->status === BudgetStatus::APPROVED;
        $plate = $budget->reception?->vehicle?->plate;
        $vehicle = $plate ? " (vehículo {$plate})" : '';

        $this->send(new WorkshopNotification(
            $approved ? InternalNotificationType::BUDGET_APPROVED : InternalNotificationType::BUDGET_REJECTED,
            $approved ? 'Presupuesto aprobado' : 'Presupuesto rechazado',
            $approved
                ? "El presupuesto {$budget->code}{$vehicle} fue aprobado: ya puede generarse la orden de trabajo."
                : "El presupuesto {$budget->code}{$vehicle} fue rechazado.",
            ['type' => 'budget', 'id' => $budget->id, 'code' => $budget->code]
        ));
    }

    public function workOrderCompleted(WorkOrder $workOrder): void
    {
        $this->send(new WorkshopNotification(
            InternalNotificationType::WORK_ORDER_COMPLETED,
            'Orden de trabajo completada',
            "La orden de trabajo {$workOrder->code} se completó y está lista para facturar.",
            ['type' => 'work_order', 'id' => $workOrder->id, 'code' => $workOrder->code]
        ));
    }

    /**
     * Línea de repuesto que quedó pendiente por falta de stock.
     */
    public function partOutOfStock(WorkOrderPart $line, Part $part): void
    {
        $workOrder = $line->workOrder;
        $quantity = rtrim(rtrim(Decimal::of($line->quantity, 3), '0'), '.');

        $this->send(new WorkshopNotification(
            InternalNotificationType::PART_OUT_OF_STOCK,
            'Repuesto sin stock',
            "La orden de trabajo {$workOrder->code} necesita {$quantity} {$part->unit->label()} de {$part->name} ({$part->sku}) y no hay stock disponible.",
            ['type' => 'work_order', 'id' => $workOrder->id, 'code' => $workOrder->code]
        ));
    }

    /**
     * Repuesto extra agregado a la OT: hay que consultar al cliente.
     */
    public function extraPartAwaitingApproval(WorkOrderPart $line, Part $part): void
    {
        $workOrder = $line->workOrder;
        $quantity = rtrim(rtrim(Decimal::of($line->quantity, 3), '0'), '.');

        $this->send(new WorkshopNotification(
            InternalNotificationType::EXTRA_PART_AWAITING_APPROVAL,
            'Repuesto extra pendiente de aprobación',
            "La orden de trabajo {$workOrder->code} agregó {$quantity} {$part->unit->label()} de {$part->name} ({$part->sku}), fuera del presupuesto. Registrá la respuesta del cliente.",
            ['type' => 'work_order', 'id' => $workOrder->id, 'code' => $workOrder->code]
        ));
    }

    /**
     * Resumen diario: un aviso por usuario y día.
     */
    public function accountsPayableOverdue(string $overdue, int $suppliersCount): int
    {
        $type = InternalNotificationType::ACCOUNTS_PAYABLE_OVERDUE;
        $amount = number_format((int) Decimal::round(Decimal::of($overdue, 2), 0), 0, ',', '.');

        $users = $this->recipients($type)->reject(
            fn (User $user) => $user->notifications()->where('type', $type->value)->whereDate('created_at', today())->exists()
        );

        Notification::send($users, new WorkshopNotification(
            $type,
            'Cuentas por pagar vencidas',
            "Hay compras vencidas con saldo por Gs. {$amount} con {$suppliersCount} proveedor(es).",
        ));

        return $users->count();
    }

    private function send(WorkshopNotification $notification): void
    {
        Notification::send($this->recipients($notification->type), $notification);
    }

    /**
     * Usuarios de los roles configurados (se ignoran los roles inexistentes).
     *
     * @return Collection<int, User>
     */
    private function recipients(InternalNotificationType $type): Collection
    {
        $roles = Role::where('guard_name', 'api')->whereIn('name', $type->recipientRoles())->pluck('name');

        return $roles->isEmpty() ? collect() : User::role($roles->all())->get();
    }
}
