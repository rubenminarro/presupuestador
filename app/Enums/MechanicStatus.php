<?php

namespace App\Enums;

enum MechanicStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ON_LEAVE = 'on_leave';

    public function label(): string {
        return match($this) {
            self::ACTIVE => 'Activo',
            self::INACTIVE => 'Inactivo',
            self::ON_LEAVE => 'En licencia',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
