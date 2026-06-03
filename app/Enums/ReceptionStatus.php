<?php

namespace App\Enums;

enum ReceptionStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case WAITING_PARTS = 'waiting_parts';
    case COMPLETED = 'completed';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';


    public function label(): string {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::IN_PROGRESS => 'En reparación',
            self::WAITING_PARTS => 'Esperando repuestos',
            self::COMPLETED => 'Terminado',
            self::DELIVERED => 'Entregado',
            self::CANCELLED => 'Cancelado',
        };
    }
}
