<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Resources\ShowBrandResource;
use App\Http\Requests\UpdateBrandRequest;
use App\Models\Brand;
use App\Traits\ApiResponse;

class BrandController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        
        $search = $request->query('search');

        $brands = Brand::when($search, function ($query, $search) {
            $query->where('name', 'like', "%{$search}%");
        })->orderBy('name')->paginate(10);
    
        return $this->successResponse(
            'Marcas obtenidas correctamente.',
            [
                'items' => ShowBrandResource::collection($brands->items()),
                'pagination' => [
                    'current_page' => $brands->currentPage(),
                    'last_page' => $brands->lastPage(),
                    'per_page' => $brands->perPage(),
                    'total' => $brands->total(),
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
            new ShowBrandResource($brand->find($brand->id)),
            201
        );
    }

    public function show(Brand $brand)
    {
        return $this->successResponse(
            'Marca encontrada.', 
            new ShowBrandResource($brand->with('brandModels')->find($brand->id))
        );
    }

    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        $data = $request->validated();

        $brand->update($data);

        return $this->successResponse(
            'Marca actualizada correctamente.',
            new ShowBrandResource($brand->with('brandModels')->find($brand->id))
        );
    }

    public function activate(Brand $brand)
    {
        $brand->update([
            'active' => !$brand->active
        ]);

        return $this->successResponse(
            $brand->active
                ? 'Marca activada correctamente.'
                : 'Marca desactivada correctamente.',
            [
                'id' => $brand->id,
                'active' => $brand->active
            ]
        );
    }
    
}