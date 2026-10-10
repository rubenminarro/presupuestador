<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientVehicleRequest;
use App\Http\Resources\ShowVehicleResource;
use App\Http\Resources\VehicleResource;
use App\Models\Client;
use App\Models\Vehicle;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Vehículos administrados desde su cliente propietario.
 * Complementa a VehicleController (/vehicles), que se mantiene sin cambios.
 */
class ClientVehicleController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:vehicles.index', only: ['index']),
            new Middleware('permission:vehicles.store', only: ['store']),
            new Middleware('permission:vehicles.show', only: ['show']),
        ];
    }

    public function index(Request $request, Client $client)
    {
        $search = trim($request->input('search', ''));

        $vehicles = $client->vehicles()
            ->with(['client', 'brand', 'vehicleModel'])
            ->when($request->filled('search'), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('plate', 'like', "%{$search}%")
                        ->orWhere('chassis', 'like', "%{$search}%")
                        ->orWhere('engine_number', 'like', "%{$search}%")
                        ->orWhereHas('brand', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('vehicleModel', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 10);

        return $this->successResponse(
            'Vehículos del cliente obtenidos correctamente.',
            VehicleResource::collection($vehicles->items()),
            200,
            [
                'pagination' => [
                    'total'       => $vehicles->total(),
                    'perPage'     => $vehicles->perPage(),
                    'currentPage' => $vehicles->currentPage(),
                    'lastPage'    => $vehicles->lastPage(),
                ]
            ]
        );
    }

    public function store(StoreClientVehicleRequest $request, Client $client)
    {
        $vehicle = $client->vehicles()->create($request->validated());

        $vehicle->load(['client', 'brand', 'vehicleModel']);

        return $this->successResponse(
            'Vehículo creado correctamente.',
            new ShowVehicleResource($vehicle),
            201
        );
    }

    public function show(Client $client, Vehicle $vehicle)
    {
        $vehicle->load(['client', 'brand', 'vehicleModel']);

        return $this->successResponse(
            'Vehículo encontrado.',
            new ShowVehicleResource($vehicle),
            200
        );
    }
}
