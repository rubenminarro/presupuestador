<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreVehicleModelRequest;
use App\Http\Resources\ShowVehicleModelResource;
use App\Http\Requests\UpdateVehicleModelRequest;
use App\Models\VehicleModel;
use App\Traits\ApiResponse;

class VehicleModelController extends Controller
{
     use ApiResponse;

    public function index(Request $request)
    {
        
        $search = $request->query('search');

        $vehicleModels = VehicleModel::when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhereHas('Brand', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                });
            });
        })->orderBy('name')->paginate(10);
    
        return $this->successResponse(
            'Modelos de vehículo obtenidos correctamente.',
            [
                'items' => ShowVehicleModelResource::collection($vehicleModels->items()),
                'pagination' => [
                    'current_page' => $vehicleModels->currentPage(),
                    'last_page' => $vehicleModels->lastPage(),
                    'per_page' => $vehicleModels->perPage(),
                    'total' => $vehicleModels->total(),
                ]
            ]
        );
    }

    public function store(StoreVehicleModelRequest $request)
    {
        $data = $request->validated();

        $vehicleModel = VehicleModel::create($data);

        return $this->successResponse(
            'Modelo de vehículo creado correctamente.',
            new ShowVehicleModelResource($vehicleModel)
        );
    }

    public function show(VehicleModel $vehicleModel)
    {
        return $this->successResponse(
            'Modelo de vehículo encontrado.', 
            new ShowVehicleModelResource($vehicleModel)
        );
    }

    public function update(UpdateVehicleModelRequest $request, VehicleModel $vehicleModel)
    {
        $data = $request->validated();

        $vehicleModel->update($data);

        return $this->successResponse(
            'Modelo de vehículo actualizado correctamente.',
            new ShowVehicleModelResource($vehicleModel)
        );
    }

    public function activate(VehicleModel $vehicleModel)
    {
        $vehicleModel->update([
            'active' => !$vehicleModel->active
        ]);

        return $this->successResponse(
            $vehicleModel->active
                ? 'Modelo de vehículo activado correctamente.'
                : 'Modelo de vehículo desactivado correctamente.',
            [
                'id' => $vehicleModel->id,
                'active' => $vehicleModel->active
            ]
        );
    }
}
