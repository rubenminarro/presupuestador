<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreVehicleModelRequest;
use App\Http\Resources\ShowVehicleModelResource;
use App\Http\Requests\UpdateVehicleModelRequest;
use App\Http\Resources\VehicleModelResource;
use App\Models\VehicleModel;
use App\Traits\ApiResponse;

class VehicleModelController extends Controller
{
     use ApiResponse;

    public function index(Request $request)
    {
        
        $vehicleModels = VehicleModel::query()
        ->with(['brand'])
        ->when($request->filled('search'), function ($query) use ($request) {
            
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('brand', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    }
                );
            });
        })
        ->latest()
        ->paginate(
            $request->per_page ?? 10
        );

        return $this->successResponse(
            'Modelos de vehículo obtenidos correctamente.',
            VehicleModelResource::collection($vehicleModels->items()),
            200,
            [
                'pagination' => [
                    'total'       => $vehicleModels->total(),
                    'perPage'     => $vehicleModels->perPage(),
                    'currentPage' => $vehicleModels->currentPage(),
                    'lastPage'    => $vehicleModels->lastPage(),
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
            new ShowVehicleModelResource($vehicleModel),
            201
        );
    }

    public function show(VehicleModel $vehicleModel)
    {
        return $this->successResponse(
            'Modelo de vehículo encontrado.', 
            new ShowVehicleModelResource($vehicleModel),
            200
        );
    }

    public function update(UpdateVehicleModelRequest $request, VehicleModel $vehicleModel)
    {
        $data = $request->validated();

        $vehicleModel->update($data);

        return $this->successResponse(
            'Modelo de vehículo actualizado correctamente.',
            new ShowVehicleModelResource($vehicleModel),
            200
        );
    }

    public function destroy(VehicleModel $vehicleModel)
    {
        
        $suffix = '//deleted_' . now()->timestamp;

        if ($vehicleModel->name) {
            $vehicleModel->name = $vehicleModel->name . $suffix;
        }

        $vehicleModel->save();
    
        $vehicleModel->delete();

        return $this->successResponse(
            'Modelo de vehículo eliminado correctamente.',
            null,
            200
        );
    }
}
