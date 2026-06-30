<?php

namespace App\Enums;

enum BudgetStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

public function label(): string {
        return match($this) {
            self::DRAFT => 'Borrador',
            self::SENT => 'Enviado',
            self::APPROVED => 'Aprobado',
            self::REJECTED => 'Rechazado',
            self::CANCELLED => 'Cancelado',
        };
    }
}
