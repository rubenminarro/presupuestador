<?php

namespace App\Traits;

trait ApiResponse
{
    protected function successResponse($message = 'OK', $data = null, $status = 200, $meta = null)
    {
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

    protected function errorResponse($message = 'Error', $errors = null, $status = 400)
    {
        return response()->json([
            'success'   => false,
            'status'    => $status,
            'message'   => $message,
            'data'      => null,
            'errors'    => $errors,
            'meta'      => null,
            'timestamp' => now()->toISOString(),
        ], $status);
    }
}
