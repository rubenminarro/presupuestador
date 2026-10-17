<?php

namespace App\Enums;

/**
 * Estado de cobro de una factura emitida (calculado, no se persiste).
 */
enum InvoicePaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PARTIAL = 'partial';
    case PAID = 'paid';

    public function label(): string {
        return match($this) {
            self::UNPAID => 'Pendiente',
            self::PARTIAL => 'Pago parcial',
            self::PAID => 'Pagada',
        };
    }
}
