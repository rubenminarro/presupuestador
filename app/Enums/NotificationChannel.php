<?php

namespace App\Enums;

/**
 * Canal de los avisos a clientes. WhatsApp queda para una fase posterior.
 */
enum NotificationChannel: string
{
    case MAIL = 'mail';

    public function label(): string {
        return match($this) {
            self::MAIL => 'Email',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
