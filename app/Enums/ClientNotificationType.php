<?php

namespace App\Enums;

enum ClientNotificationType: string
{
    case BUDGET_SENT = 'budget_sent';
    case VEHICLE_READY = 'vehicle_ready';
    case INVOICE_DUE_SOON = 'invoice_due_soon';
    case INVOICE_OVERDUE = 'invoice_overdue';
    case MAINTENANCE_DUE_SOON = 'maintenance_due_soon';
    case MAINTENANCE_OVERDUE = 'maintenance_overdue';

    public function label(): string {
        return match($this) {
            self::BUDGET_SENT => 'Presupuesto enviado',
            self::VEHICLE_READY => 'Vehículo listo',
            self::INVOICE_DUE_SOON => 'Factura por vencer',
            self::INVOICE_OVERDUE => 'Factura vencida',
            self::MAINTENANCE_DUE_SOON => 'Mantenimiento próximo',
            self::MAINTENANCE_OVERDUE => 'Mantenimiento vencido',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
