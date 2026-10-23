<?php

namespace App\Enums;

/**
 * Medio por el que el cliente respondió (aprobación o rechazo de un extra).
 */
enum ClientDecisionChannel: string
{
    case PHONE = 'phone';
    case WHATSAPP = 'whatsapp';
    case IN_PERSON = 'in_person';
    case EMAIL = 'email';
    case OTHER = 'other';

    public function label(): string {
        return match($this) {
            self::PHONE => 'Teléfono',
            self::WHATSAPP => 'WhatsApp',
            self::IN_PERSON => 'Presencial',
            self::EMAIL => 'Email',
            self::OTHER => 'Otro',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
