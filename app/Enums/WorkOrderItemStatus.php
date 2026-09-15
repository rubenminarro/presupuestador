<?php

namespace App\Enums;

enum WorkOrderItemStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';


    public function label(): string {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::IN_PROGRESS => 'En reparación',
            self::COMPLETED => 'Terminado',
            self::CANCELLED => 'Cancelado',
        };
    }
}
