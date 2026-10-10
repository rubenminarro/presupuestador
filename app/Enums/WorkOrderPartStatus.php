<?php

namespace App\Enums;

enum WorkOrderPartStatus: string
{
    case PENDING = 'pending';
    case RESERVED = 'reserved';
    case CONSUMED = 'consumed';
    case RETURNED = 'returned';
    case CANCELLED = 'cancelled';

    public function label(): string {
        return match($this) {
            self::PENDING => 'Pendiente de stock',
            self::RESERVED => 'Reservado',
            self::CONSUMED => 'Consumido',
            self::RETURNED => 'Devuelto',
            self::CANCELLED => 'Cancelado',
        };
    }

    /**
     * Línea sin resolver: todavía no se consumió ni se canceló.
     */
    public function isOpen(): bool
    {
        return in_array($this, self::openStatuses(), true);
    }

    public static function openStatuses(): array
    {
        return [self::PENDING, self::RESERVED];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
