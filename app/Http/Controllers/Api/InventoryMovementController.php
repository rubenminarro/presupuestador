<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexInventoryMovementRequest;
use App\Http\Resources\InventoryMovementResource;
use App\Http\Resources\ShowInventoryMovementResource;
use App\Models\InventoryMovement;
use App\Models\Part;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class InventoryMovementController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory.index', only: ['index']),
            new Middleware('permission:inventory.show', only: ['show', 'partMovements']),
        ];
    }

    public function index(IndexInventoryMovementRequest $request)
    {
        $query = InventoryMovement::query()->with(['part', 'creator']);

        return $this->paginatedResponse(
            $this->applyFilters($query, $request),
            $request,
            'Movimientos de inventario obtenidos correctamente.'
        );
    }

    public function partMovements(IndexInventoryMovementRequest $request, Part $part)
    {
        $query = $part->movements()->getQuery()->with(['creator']);

        return $this->paginatedResponse(
            $this->applyFilters($query, $request),
            $request,
            'Movimientos del repuesto obtenidos correctamente.'
        );
    }

    public function show(InventoryMovement $movement)
    {
        $movement->load(['part.category', 'warehouse', 'supplier', 'creator']);

        return $this->successResponse(
            'Movimiento de inventario obtenido correctamente.',
            new ShowInventoryMovementResource($movement),
            200
        );
    }

    private function applyFilters(Builder $query, IndexInventoryMovementRequest $request): Builder
    {
        return $query
            ->when($request->filled('part_id'), fn ($q) => $q->where('part_id', $request->part_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('created_by'), fn ($q) => $q->where('created_by', $request->created_by))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderByDesc('id');
    }

    private function paginatedResponse(Builder $query, IndexInventoryMovementRequest $request, string $message)
    {
        $movements = $query->paginate($request->per_page ?? 10);

        return $this->successResponse(
            $message,
            InventoryMovementResource::collection($movements->items()),
            200,
            [
                'pagination' => [
                    'total'       => $movements->total(),
                    'perPage'     => $movements->perPage(),
                    'currentPage' => $movements->currentPage(),
                    'lastPage'    => $movements->lastPage(),
                ]
            ]
        );
    }
}
