<?php

namespace App\Enums;

enum CheckListType: string
{
    case BOOLEAN = 'boolean';
    case TEXT = 'text';
    case NUMBER = 'number';

    public function label(): string {
        return match($this) {
            self::BOOLEAN => 'Booleano',
            self::TEXT => 'Texto',
            self::NUMBER => 'Número',
        };
    }
}
