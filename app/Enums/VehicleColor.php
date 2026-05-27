<?php

namespace App\Enums;

enum VehicleColor: string
{
    case WHITE = 'white';
    case BLACK = 'black';
    case GRAY = 'gray';
    case SILVER = 'silver';
    case RED = 'red';
    case BLUE = 'blue';
    case GREEN = 'green';
    case YELLOW = 'yellow';
    case ORANGE = 'orange';
    case BROWN = 'brown';
    case BEIGE = 'beige';

    public function label(): string {
        return match($this) {
            self::WHITE => 'Blanco',
            self::BLACK => 'Negro',
            self::GRAY => 'Gris',
            self::SILVER => 'Plata / Plateado',
            self::RED => 'Rojo',
            self::BLUE => 'Azul',
            self::GREEN => 'Verde',
            self::YELLOW => 'Amarillo',
            self::ORANGE => 'Naranja',
            self::BROWN => 'Marrón / Café',
            self::BEIGE => 'Beige',
        };
    }
    
}
