<?php

namespace App\Traits;

use Illuminate\Http\Response;

/**
 * Descarga de archivos generados (PDF, CSV). Los errores siguen saliendo
 * en JSON con el contrato de ApiResponse.
 */
trait FileResponse
{
    /**
     * @param array{filename: string, content: string} $file
     */
    protected function fileResponse(array $file, string $contentType): Response
    {
        return response($file['content'], 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $file['filename'] . '"',
        ]);
    }
}
