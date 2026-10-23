<?php

namespace App\Enums;

/**
 * Respuesta del cliente a un repuesto extra no presupuestado.
 */
enum ClientDecision: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string {
        return match($this) {
            self::APPROVED => 'Aprobado por el cliente',
            self::REJECTED => 'Rechazado por el cliente',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
