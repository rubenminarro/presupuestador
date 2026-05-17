<?php

namespace App\Enums;

enum BudgetItemType: string
{
    case LABOR = 'labor';
    case PART = 'part';
    case SERVICE = 'service';

    public function label(): string {
        return match($this) {
            self::LABOR => 'Trabajo',
            self::PART => 'Pieza',
            self::SERVICE => 'Servicio',
        };
    }
}
