<?php

namespace App\Enums;

enum FuelLevel: string
{
    case EMPTY = 'empty';
    case QUARTER = 'quarter';
    case HALF = 'half';
    case THREE_QUARTERS = 'three_quarters';
    case FULL = 'full';

    public function label(): string {
        return match($this) {
            self::EMPTY => 'Vacio',
            self::QUARTER => 'Un cuarto',
            self::HALF => 'La mitad',
            self::THREE_QUARTERS => 'Tres cuartos',
            self::FULL => 'Lleno',
        };
    }
}
