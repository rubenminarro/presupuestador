<?php

namespace App\Enums;

/**
 * Estado de un mantenimiento de un vehículo (calculado, no se persiste).
 */
enum MaintenanceStatus: string
{
    case UNKNOWN = 'unknown';
    case OK = 'ok';
    case DUE_SOON = 'due_soon';
    case OVERDUE = 'overdue';

    public function label(): string {
        return match($this) {
            self::UNKNOWN => 'Sin registro',
            self::OK => 'Al día',
            self::DUE_SOON => 'Por vencer',
            self::OVERDUE => 'Vencido',
        };
    }

    /**
     * Gravedad para combinar el vencimiento por km y por fecha.
     */
    public function severity(): int
    {
        return match($this) {
            self::UNKNOWN => 0,
            self::OK => 1,
            self::DUE_SOON => 2,
            self::OVERDUE => 3,
        };
    }
}
