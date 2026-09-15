<?php

namespace App\Enums;

enum WorkOrderItemType: string
{
    case LABOR = 'labor';
    case PART = 'part';
    case SERVICE = 'service';

    public function label(): string
    {
        return match ($this) {
            self::LABOR => 'Mano de obra',
            self::PART => 'Repuesto',
            self::SERVICE => 'Servicio',
        };
    }
}
