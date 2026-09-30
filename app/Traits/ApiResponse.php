<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse(
        string $message = 'OK',
        mixed $data = null,
        int $status = 200,
        mixed $meta = null
    ): JsonResponse {
        return response()->json([
            'success'   => true,
            'status'    => $status,
            'message'   => $message,
            'data'      => $data,
            'errors'    => null,
            'meta'      => $meta,
            'timestamp' => now()->toISOString(),
        ], $status);
    }

    protected function errorResponse(
        string $message = 'Error',
        mixed $errors = null,
        int $status = 400,
        mixed $meta = null
    ): JsonResponse {
        return response()->json([
            'success'   => false,
            'status'    => $status,
            'message'   => $message,
            'data'      => null,
            'errors'    => $errors,
            'meta'      => $meta,
            'timestamp' => now()->toISOString(),
        ], $status);
    }
}