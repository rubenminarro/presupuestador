<?php

namespace App\Enums;

enum WorkOrderPartStatus: string
{
    case AWAITING_APPROVAL = 'awaiting_approval';
    case PENDING = 'pending';
    case RESERVED = 'reserved';
    case CONSUMED = 'consumed';
    case RETURNED = 'returned';
    case CANCELLED = 'cancelled';

    public function label(): string {
        return match($this) {
            self::AWAITING_APPROVAL => 'Pendiente de aprobación',
            self::PENDING => 'Pendiente de stock',
            self::RESERVED => 'Reservado',
            self::CONSUMED => 'Consumido',
            self::RETURNED => 'Devuelto',
            self::CANCELLED => 'Cancelado',
        };
    }

    /**
     * Línea sin resolver: todavía no se consumió ni se canceló (un extra sin
     * la decisión del cliente también impide completar la OT).
     */
    public function isOpen(): bool
    {
        return in_array($this, self::openStatuses(), true);
    }

    public static function openStatuses(): array
    {
        return [self::AWAITING_APPROVAL, self::PENDING, self::RESERVED];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
