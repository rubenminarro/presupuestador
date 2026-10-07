<?php

namespace App\Enums;

enum PartUnit: string
{
    case UNIT = 'unit';
    case LITER = 'liter';
    case KILOGRAM = 'kilogram';
    case METER = 'meter';
    case SET = 'set';
    case KIT = 'kit';

    public function label(): string {
        return match($this) {
            self::UNIT => 'Unidad',
            self::LITER => 'Litro',
            self::KILOGRAM => 'Kilogramo',
            self::METER => 'Metro',
            self::SET => 'Juego',
            self::KIT => 'Kit',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
