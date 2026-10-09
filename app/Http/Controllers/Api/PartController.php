<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartRequest;
use App\Http\Requests\UpdatePartRequest;
use App\Http\Resources\PartResource;
use App\Http\Resources\ShowPartResource;
use App\Models\Part;
use App\Services\PartService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PartController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public function __construct(
        protected PartService $partService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:parts.index', only: ['index']),
            new Middleware('permission:parts.store', only: ['store']),
            new Middleware('permission:parts.show', only: ['show']),
            new Middleware('permission:parts.update', only: ['update']),
            new Middleware('permission:parts.destroy', only: ['destroy']),
            new Middleware(
                'permission:parts.change-status',
                only: ['activate', 'deactivate', 'discontinue']
            ),
        ];
    }

    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $query = Part::query()->with(['category']);

        $query->when($request->filled('search'), function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('part_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        });

        $query->when($request->filled('category_id'), function ($q) use ($request) {
            $q->where('part_category_id', $request->category_id);
        });

        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->status);
        });

        $query->when($request->filled('brand'), function ($q) use ($request) {
            $q->where('brand', $request->brand);
        });

        $query->when($request->filled('supplier_id'), function ($q) use ($request) {
            $q->whereHas('suppliers', function ($supplierQuery) use ($request) {
                $supplierQuery->where('suppliers.id', $request->supplier_id);
            });
        });

        $parts = $query
            ->orderBy('name')
            ->paginate($request->per_page ?? 10);

        return $this->successResponse(
            'Repuestos obtenidos correctamente.',
            PartResource::collection($parts->items()),
            200,
            [
                'pagination' => [
                    'total'       => $parts->total(),
                    'perPage'     => $parts->perPage(),
                    'currentPage' => $parts->currentPage(),
                    'lastPage'    => $parts->lastPage(),
                ]
            ]
        );
    }

    public function show(Part $part)
    {
        $part->load(['category', 'suppliers']);

        return $this->successResponse(
            'Repuesto obtenido correctamente.',
            new ShowPartResource($part),
            200
        );
    }

    public function store(StorePartRequest $request)
    {
        $part = $this->partService->create($request->validated());

        return $this->successResponse(
            'Repuesto creado correctamente.',
            new ShowPartResource($part),
            201
        );
    }

    public function update(UpdatePartRequest $request, Part $part)
    {
        $part = $this->partService->update($part, $request->validated());

        return $this->successResponse(
            'Repuesto actualizado correctamente.',
            new ShowPartResource($part),
            200
        );
    }

    public function activate(Part $part)
    {
        $part = $this->partService->activate($part);

        return $this->successResponse(
            'Repuesto activado correctamente.',
            new ShowPartResource($part),
            200
        );
    }

    public function deactivate(Part $part)
    {
        $part = $this->partService->deactivate($part);

        return $this->successResponse(
            'Repuesto desactivado correctamente.',
            new ShowPartResource($part),
            200
        );
    }

    public function discontinue(Part $part)
    {
        $part = $this->partService->discontinue($part);

        return $this->successResponse(
            'Repuesto descontinuado correctamente.',
            new ShowPartResource($part),
            200
        );
    }

    public function destroy(Part $part)
    {
        $this->partService->delete($part);

        return $this->successResponse(
            'Repuesto eliminado correctamente.',
            null,
            200
        );
    }
}