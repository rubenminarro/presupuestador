<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case CARD = 'card';
    case TRANSFER = 'transfer';
    case CHECK = 'check';

    public function label(): string {
        return match($this) {
            self::CASH => 'Efectivo',
            self::CARD => 'Tarjeta',
            self::TRANSFER => 'Transferencia',
            self::CHECK => 'Cheque',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
