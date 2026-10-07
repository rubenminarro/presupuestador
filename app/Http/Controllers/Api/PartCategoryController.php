<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartCategoryRequest;
use App\Http\Requests\UpdatePartCategoryRequest;
use App\Http\Resources\PartCategoryResource;
use App\Models\PartCategory;
use App\Services\PartService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PartCategoryController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public function __construct(
        protected PartService $partService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:part-categories.index', only: ['index']),
            new Middleware('permission:part-categories.store', only: ['store']),
            new Middleware('permission:part-categories.show', only: ['show']),
            new Middleware('permission:part-categories.update', only: ['update']),
            new Middleware('permission:part-categories.destroy', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $categories = PartCategory::query()
            ->withCount('parts')
            ->when($request->filled('search'), function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->orderBy('name')
            ->paginate($request->input('per_page', 10));

        return $this->successResponse(
            'Categorías de repuestos obtenidas correctamente.',
            PartCategoryResource::collection($categories->items()),
            200,
            [
                'pagination' => [
                    'total'       => $categories->total(),
                    'perPage'     => $categories->perPage(),
                    'currentPage' => $categories->currentPage(),
                    'lastPage'    => $categories->lastPage(),
                ]
            ]
        );
    }

    public function store(StorePartCategoryRequest $request)
    {
        $category = PartCategory::create($request->validated());

        return $this->successResponse(
            'Categoría de repuestos creada correctamente.',
            new PartCategoryResource($category->refresh()),
            201
        );
    }

    public function show(PartCategory $partCategory)
    {
        $partCategory->loadCount('parts');

        return $this->successResponse(
            'Categoría de repuestos obtenida correctamente.',
            new PartCategoryResource($partCategory),
            200
        );
    }

    public function update(UpdatePartCategoryRequest $request, PartCategory $partCategory)
    {
        $partCategory->update($request->validated());

        return $this->successResponse(
            'Categoría de repuestos actualizada correctamente.',
            new PartCategoryResource($partCategory),
            200
        );
    }

    public function destroy(PartCategory $partCategory)
    {
        $this->partService->deleteCategory($partCategory);

        return $this->successResponse(
            'Categoría de repuestos eliminada correctamente.',
            null,
            200
        );
    }
}
