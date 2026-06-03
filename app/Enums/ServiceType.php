<?php

namespace App\Enums;

enum ServiceType: string
{
    case MECHANICAL = 'mechanical';
    case BODYWORK = 'bodywork';
    case MIXED = 'mixed';

    public function label(): string {
        return match($this) {
            self::MECHANICAL => 'Mecánico',
            self::BODYWORK => 'Carrocería',
            self::MIXED => 'Mixto',
        };
    }
}
