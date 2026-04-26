<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBrandModelRequest;
use App\Http\Resources\ShowBrandModelResource;
use App\Http\Requests\UpdateBrandModelRequest;
use App\Models\BrandModel;
use App\Traits\ApiResponse;

class BrandModelController extends Controller
{
     use ApiResponse;

    public function index(Request $request)
    {
        
        $search = $request->query('search');

        $brandModels = BrandModel::when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhereHas('Brand', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                });
            });
        })->orderBy('name')->paginate(10);
    
        return $this->successResponse(
            'Modelos de marca obtenidos correctamente.',
            [
                'items' => ShowBrandModelResource::collection($brandModels->items()),
                'pagination' => [
                    'current_page' => $brandModels->currentPage(),
                    'last_page' => $brandModels->lastPage(),
                    'per_page' => $brandModels->perPage(),
                    'total' => $brandModels->total(),
                ]
            ]
        );
    }

    public function store(StoreBrandModelRequest $request)
    {
        $data = $request->validated();

        $brandModel = BrandModel::create($data);

        return $this->successResponse(
            'Modelo de marca creado correctamente.',
            new ShowBrandModelResource($brandModel)
        );
    }

    public function show(BrandModel $brandModel)
    {
        return $this->successResponse(
            'Modelo de marca encontrado.', 
            new ShowBrandModelResource($brandModel)
        );
    }

    public function update(UpdateBrandModelRequest $request, BrandModel $brandModel)
    {
        $data = $request->validated();

        $brandModel->update($data);

        return $this->successResponse(
            'Modelo de marca actualizado correctamente.',
            new ShowBrandModelResource($brandModel)
        );
    }

    public function activate(BrandModel $brandModel)
    {
        $brandModel->update([
            'active' => !$brandModel->active
        ]);

        return $this->successResponse(
            $brandModel->active
                ? 'Modelo de marca activado correctamente.'
                : 'Modelo de marca desactivado correctamente.',
            [
                'id' => $brandModel->id,
                'active' => $brandModel->active
            ]
        );
    }
}
