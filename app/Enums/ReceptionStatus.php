<?php

namespace App\Enums;

enum ReceptionStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case REPAIR = 'repair';
    case COMPLETED = 'completed';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';


    public function label(): string {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::IN_PROGRESS => 'En reparación',
            self::REPAIR => 'En reparación',
            self::COMPLETED => 'Terminado',
            self::DELIVERED => 'Entregado',
            self::CANCELLED => 'Cancelado',
        };
    }

    /**
     * Recepción abierta: el vehículo sigue en el taller (todo salvo entregada o
     * cancelada). Un vehículo no puede tener dos recepciones abiertas.
     */
    public static function openStatuses(): array
    {
        return [self::PENDING, self::IN_PROGRESS, self::REPAIR, self::COMPLETED];
    }

    public function isOpen(): bool
    {
        return in_array($this, self::openStatuses(), true);
    }
}
