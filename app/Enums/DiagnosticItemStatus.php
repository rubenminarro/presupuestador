<?php

namespace App\Enums;

enum DiagnosticItemStatus: string
{
    case PENDING = 'pending';
    case OK = 'ok';
    case OBSERVATION = 'observation';
    case REPAIR_REQUIRED = 'repair_required';
    case REPLACE_REQUIRED = 'replace_required';
    case NOT_APPLICABLE = 'not_applicable';

    public function label(): string {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::OK => 'OK',
            self::OBSERVATION => 'Observación',
            self::REPAIR_REQUIRED => 'Reparación Requerida',
            self::REPLACE_REQUIRED => 'Reemplazo Requerido',
            self::NOT_APPLICABLE => 'No Aplicable',
        };
    }
}
