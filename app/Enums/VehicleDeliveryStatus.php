<?php

namespace App\Enums;

enum VehicleDeliveryStatus: string
{
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string {
        return match($this) {
            self::DELIVERED => 'Entregado',
            self::CANCELLED => 'Anulado',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
