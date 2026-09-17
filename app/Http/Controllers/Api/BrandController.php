<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Resources\ShowBrandResource;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BrandController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:brands.index', only: ['index']),
            new Middleware('permission:brands.store', only: ['store']),
            new Middleware('permission:brands.show', only: ['show']),
            new Middleware('permission:brands.update', only: ['update']),
            new Middleware('permission:brands.destroy', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        
        $brands = Brand::when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        })
        ->latest()
        ->paginate(
            $request->per_page ?? 10
        );
    
        return $this->successResponse(
            'Marcas obtenidas correctamente.',
            BrandResource::collection($brands->items()),
            200,
            [
                'pagination' => [
                    'total'       => $brands->total(),
                    'perPage'     => $brands->perPage(),
                    'currentPage' => $brands->currentPage(),
                    'lastPage'    => $brands->lastPage(),
                ]
            ]
        );
    }

    public function store(StoreBrandRequest $request)
    {
        
        $data = $request->validated();

        $brand = Brand::create($data);
    
        return $this->successResponse(
            'Marca creada correctamente.',
            new ShowBrandResource($brand),
            201
        );
    }

    public function show(Brand $brand)
    {
        
        return $this->successResponse(
            'Marca encontrada.', 
            new ShowBrandResource($brand),
            200
        );
    }

    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        $data = $request->validated();

        $brand->update($data);

        return $this->successResponse(
            'Marca actualizada correctamente.',
            new ShowBrandResource($brand),
            200
        );
    }

    public function destroy(Brand $brand)
    {
        
        $suffix = '//deleted_' . now()->timestamp;

        if ($brand->name) {
            $brand->name = $brand->name . $suffix;
        }

        $brand->save();

        $brand->delete();

        return $this->successResponse(
            'Marca eliminada correctamente.',
            null,
            200
        );
    }
    
}