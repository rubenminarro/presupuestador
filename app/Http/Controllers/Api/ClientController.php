<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ShowClientResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use App\Models\Client;
use App\Traits\ApiResponse;

class ClientController extends Controller
{
    
    use ApiResponse;
    
    public function store(StoreClientRequest $request)
    {
        $data = $request->validated();

        $client = Client::create($data);

        $client->vehicles()->createMany($data['vehicles']);

        $client->load('vehicles');

        return $this->successResponse(
            'Cliente creado correctamente.',
            new ShowClientResource($client),
            201
        );
    }

    public function show(Client $client)
    {
        return $this->successResponse('Cliente encontrado.', new  ShowClientResource($client->load('vehicles')));
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        
        $data = $request->validated();

        DB::transaction(function () use ($client, $data) {

            $client->update($data);
        
            if (array_key_exists('vehicles', $data)) {
                
                $incoming = collect($data['vehicles']);

                $incomingIds = $incoming->pluck('id')->filter()->toArray();

                $client->vehicles()
                ->whereNotIn('id', $incomingIds)
                ->update(['active' => false]);

                foreach ($incoming as $vehicleData) {

                    $payload = Arr::only($vehicleData, [
                        'brand_id',
                        'vehicle_model_id',
                        'plate',
                        'color',
                        'notes',
                        'no_plate'
                    ]);

                    $payload['active'] = true;

                    if (isset($vehicleData['id'])) {
                        $vehicle = $client->vehicles()->find($vehicleData['id']);
                        if ($vehicle) {
                            $vehicle->update($payload);
                        }
                    } else {
                        $client->vehicles()->create($payload);
                    }
                }
            }

        });

        return $this->successResponse(
            'Cliente actualizado correctamente.',
            new ShowClientResource($client->load('vehicles'))
        );
    }

    public function activate(Client $client)
    {
        $client->update(['active' => !$client->active]);

        $data = [
            'id' => $client->id,
            'active' => $client->active,
        ];

        $message = $client->active ? 'Cliente activado correctamente.' : 'Cliente desactivado correctamente.';

        return $this->successResponse($message, $data);
    }

    public function destroy(Client $client)
    {
        
        if (!$client->canBeDeleted()) {

            return $this->errorResponse('Hubo un error al eliminar el cliente.', ['name' => ['Este cliente está en uso. Desactívalo en lugar de borrarlo.']], 422);

        }
        
        $client->delete();
        
        return $this->successResponse('Cliente eliminado correctamente.');
    }
}
