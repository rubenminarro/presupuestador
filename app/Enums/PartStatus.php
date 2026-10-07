<?php

namespace App\Enums;

enum PartStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DISCONTINUED = 'discontinued';

    public function label(): string {
        return match($this) {
            self::ACTIVE => 'Activo',
            self::INACTIVE => 'Inactivo',
            self::DISCONTINUED => 'Descontinuado',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match($this) {
            self::ACTIVE => in_array($status, [self::INACTIVE, self::DISCONTINUED], true),
            self::INACTIVE => in_array($status, [self::ACTIVE, self::DISCONTINUED], true),
            self::DISCONTINUED => false,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
