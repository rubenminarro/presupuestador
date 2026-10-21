<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexAuditLogRequest;
use App\Http\Resources\AuditLogResource;
use App\Http\Resources\ShowAuditLogResource;
use App\Models\AuditLog;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Consulta de la auditoría (solo lectura; los registros son inmutables).
 */
class AuditLogController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:audit-logs.index', only: ['index']),
            new Middleware('permission:audit-logs.show', only: ['show']),
        ];
    }

    public function index(IndexAuditLogRequest $request)
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('auditable_type'), fn ($q) => $q->where('auditable_type', $request->auditable_type))
            ->when($request->filled('auditable_id'), fn ($q) => $q->where('auditable_id', $request->auditable_id))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->event))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderByDesc('id')
            ->paginate($request->per_page ?? 20);

        return $this->successResponse(
            'Registros de auditoría obtenidos correctamente.',
            AuditLogResource::collection($logs->items()),
            200,
            [
                'pagination' => [
                    'total'       => $logs->total(),
                    'perPage'     => $logs->perPage(),
                    'currentPage' => $logs->currentPage(),
                    'lastPage'    => $logs->lastPage(),
                ]
            ]
        );
    }

    public function show(AuditLog $auditLog)
    {
        return $this->successResponse(
            'Registro de auditoría obtenido correctamente.',
            new ShowAuditLogResource($auditLog->load('user')),
            200
        );
    }
}
