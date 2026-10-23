<?php

namespace App\Services;

use App\Enums\DiagnosticStatus;
use App\Exceptions\DiagnosticException;
use App\Models\Diagnostic;

/**
 * Reglas de edición del diagnóstico: con la recepción cerrada no se toca nada; terminado o cancelado, sus ítems y
 * fotos quedan fijos (el diagnóstico puede volver a "en proceso" para corregirlos).
 */
class DiagnosticService
{
    public function __construct(
        protected ReceptionStatusService $receptionStatusService
    ) {
    }

    /**
     * Editar o eliminar el diagnóstico: solo con la recepción abierta.
     */
    public function ensureEditable(Diagnostic $diagnostic): void
    {
        $this->receptionStatusService->ensureEditable($diagnostic->reception_id);
    }

    /**
     * Agregar, editar o eliminar ítems y sus fotos: recepción abierta y diagnóstico pendiente o en proceso.
     */
    public function ensureItemsEditable(Diagnostic $diagnostic): void
    {
        $this->ensureEditable($diagnostic);

        $status = DiagnosticStatus::tryFrom((string) $diagnostic->status);

        if ($status?->isClosed()) {
            throw new DiagnosticException(
                "El diagnóstico está {$this->lower($status)}: sus ítems y fotos no se modifican. Vuelva a ponerlo en proceso para corregirlo.",
                422,
                'DIAGNOSTIC_NOT_EDITABLE'
            );
        }
    }

    private function lower(DiagnosticStatus $status): string
    {
        return mb_strtolower($status->label());
    }
}
