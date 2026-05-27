<?php

namespace App\Enums;

enum TransmissionType: string
{
    case MANUAL = 'manual';
    case AUTOMATIC = 'automatic';
    case SEMI_AUTOMATIC = 'semi-automatic';

    public function label(): string {
        return match($this) {
            self::MANUAL => 'Manual',
            self::AUTOMATIC => 'Automática',
            self::SEMI_AUTOMATIC => 'Semi-automática',
        };
    }
    
}
