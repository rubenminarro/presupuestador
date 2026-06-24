<?php

namespace App\Enums;

enum CheckListValue: string
{
    case GOOD = 'good';
    case BAD = 'bad';
    case NA = 'na';

    public function label(): string {
        return match($this) {
            self::GOOD => 'Bueno',
            self::BAD => 'Malo',
            self::NA => 'No Aplica',
        };
    }
}
