<?php

namespace App\Enums;

enum FuelType: string
{
    case GASOLINE = 'gasoline';
    case DIESEL = 'diesel';
    case ELECTRIC = 'electric';
    case HYBRID = 'hybrid';
    case FLEX = 'flex';
    case GLP = 'glp';
    case GNV = 'gnv';

    public function label(): string {
        return match($this) {
            self::GASOLINE => 'Gasolina',
            self::DIESEL => 'Diésel',
            self::ELECTRIC => 'Eléctrico',
            self::HYBRID => 'Híbrido',
            self::FLEX => 'Flex (Etanol/Gasolina)',
            self::GLP => 'Gas LP',
            self::GNV => 'Gas Natural',
        };
    }
}
