<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case CANCELLED = 'cancelled';

    public function label(): string {
        return match($this) {
            self::DRAFT => 'Borrador',
            self::ISSUED => 'Emitida',
            self::CANCELLED => 'Anulada',
        };
    }

    /**
     * Factura vigente de la OT: impide generar otra.
     */
    public static function currentStatuses(): array
    {
        return [self::DRAFT, self::ISSUED];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
