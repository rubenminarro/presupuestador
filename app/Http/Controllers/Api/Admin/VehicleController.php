<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Traits\ApiResponse;

class VehicleController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $search = $request->query('search');

        $vehicles = Vehicle::with([
            'client',
            'brand',
            'brandModel',
        ])->when($search, function ($query) use ($search) {
                $query->where('plate', 'like', "%{$search}%")
                    ->orWhere('chassis', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('brand', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('brandModel', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
        })->latest()->paginate(10);

        return $this->successResponse(
            'Vehículos obtenidos correctamente.',
            [
                'items' => VehicleResource::collection($vehicles->items()),
                'pagination' => [
                    'current_page' => $vehicles->currentPage(),
                    'last_page' => $vehicles->lastPage(),
                    'per_page' => $vehicles->perPage(),
                    'total' => $vehicles->total(),
                ]
            ],
            201
        );
    }

    public function store(StoreVehicleRequest $request)
    {
        $vehicle = Vehicle::create($request->validated());

        $vehicle->load([
            'client',
            'brand',
            'brandModel',
        ]);

        return $this->successResponse(
            'Vehículo creado correctamente.',
            new VehicleResource($vehicle),
            201
        );
    }

    public function show(Vehicle $vehicle)
    {
        $vehicle->load([
            'client',
            'brand',
            'brandModel',
        ]);

        return $this->successResponse(
            'Vehículo encontrado.',
            new VehicleResource($vehicle),
            201
        );
    }
    
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        $vehicle->update($request->validated());

        $vehicle->load([
            'client',
            'brand',
            'brandModel',
        ]);

        return $this->successResponse(
            'Vehículo actualizado correctamente.',
            new VehicleResource($vehicle),
            201
        );
    }

    public function activate(Vehicle $vehicle)
    {
        $vehicle->update([
            'active' => !$vehicle->active
        ]);

        return $this->successResponse(
            $vehicle->active
                ? 'Vehículo activado correctamente.'
                : 'Vehículo desactivado correctamente.',
            [
                'id' => $vehicle->id,
                'active' => $vehicle->active
            ]
        );
    }

    public function findByPlate(string $plate)
    {
        $vehicle = Vehicle::with([
                'client',
                'brand',
                'brandModel',
            ])
            ->where('plate', $plate)
            ->where('active', true)
            ->first();

        if (!$vehicle) {
            return $this->errorResponse(
                'Vehículo no encontrado.',
                404
            );
        }

        return $this->successResponse(
            'Vehículo encontrado.',
            new VehicleResource($vehicle),
            201
        );
    }
}