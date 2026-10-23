<?php

namespace App\Enums;

/**
 * Avisos de la bandeja interna (se guarda en notifications.type).
 */
enum InternalNotificationType: string
{
    case BUDGET_APPROVED = 'budget_approved';
    case BUDGET_REJECTED = 'budget_rejected';
    case WORK_ORDER_COMPLETED = 'work_order_completed';
    case PART_OUT_OF_STOCK = 'part_out_of_stock';
    case EXTRA_PART_AWAITING_APPROVAL = 'extra_part_awaiting_approval';
    case ACCOUNTS_PAYABLE_OVERDUE = 'accounts_payable_overdue';

    public function label(): string {
        return match($this) {
            self::BUDGET_APPROVED => 'Presupuesto aprobado',
            self::BUDGET_REJECTED => 'Presupuesto rechazado',
            self::WORK_ORDER_COMPLETED => 'Orden de trabajo completada',
            self::PART_OUT_OF_STOCK => 'Repuesto sin stock',
            self::EXTRA_PART_AWAITING_APPROVAL => 'Repuesto extra pendiente de aprobación',
            self::ACCOUNTS_PAYABLE_OVERDUE => 'Cuentas por pagar vencidas',
        };
    }

    /**
     * Roles que reciben el aviso (config/notifications.php).
     *
     * @return string[]
     */
    public function recipientRoles(): array
    {
        return config("notifications.internal_recipients.{$this->value}", []);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
