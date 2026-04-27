<?php

namespace App\Enums;

enum Status: string
{
    case PENDING = 'pending';
    case DIAGNOSIS = 'diagnosis';
    case APPROVED = 'approved';
    case IN_PROGRESS = 'in_progress';
    case WAITING_PARTS = 'waiting_parts';
    case COMPLETED = 'completed';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::DIAGNOSIS => 'En diagnóstico',
            self::APPROVED => 'Aprobado',
            self::IN_PROGRESS => 'En reparación',
            self::WAITING_PARTS => 'Esperando repuestos',
            self::COMPLETED => 'Terminado',
            self::DELIVERED => 'Entregado',
            self::CANCELLED => 'Cancelado',
        };
    }
}
