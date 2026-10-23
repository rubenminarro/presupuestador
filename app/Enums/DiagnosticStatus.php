<?php

namespace App\Enums;

enum DiagnosticStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';


    public function label(): string {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::IN_PROGRESS => 'En reparación',
            self::COMPLETED => 'Terminado',
            self::CANCELLED => 'Cancelado',
        };
    }

    /**
     * Terminado o cancelado: sus ítems y fotos no se modifican (se puede volver a in_progress para corregir).
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED], true);
    }
}
