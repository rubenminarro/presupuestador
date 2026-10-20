<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case DRAFT = 'draft';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public function label(): string {
        return match($this) {
            self::DRAFT => 'Borrador',
            self::RECEIVED => 'Recibida',
            self::CANCELLED => 'Anulada',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
