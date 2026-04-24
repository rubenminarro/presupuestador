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

        return DB::transaction(function () use ($data) {
            
            $client = Client::create($data);

            $client->vehicles()->createMany($data['vehicles']);
            
            return $this->successResponse(
                'Cliente creado correctamente.',
                new ShowClientResource($client->load('vehicles')),
                201
            );
        });
    }

    public function show(Client $client)
    {
        return $this->successResponse('Cliente encontrado.', new  ShowClientResource($client->load('vehicles')));
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $data = $request->validated();

        DB::transaction(function () use ($client, $data) {
            
            $client->update(Arr::except($data, ['vehicles']));

            if (isset($data['vehicles'])) {
                
                $incoming = collect($data['vehicles']);
                
                // Obtenemos IDs de los vehículos que SI existen para no inactivarlos
                $incomingIds = $incoming->pluck('id')->filter()->toArray();

                // 2. Inactivar (active = false) los vehículos del cliente que NO están en el request
                $client->vehicles()
                    ->whereNotIn('id', $incomingIds)
                    ->update(['active' => false]);

                // 3. Procesar cada vehículo del request
                foreach ($incoming as $vehicleData) {
                    // updateOrCreate busca por ID. Si el ID es null o no existe, CREA uno nuevo.
                    // Si el ID existe, ACTUALIZA los datos.
                    $client->vehicles()->updateOrCreate(
                        ['id' => $vehicleData['id'] ?? null], 
                        array_merge($vehicleData, ['active' => true])
                    );
                }
            }
        });

        return $this->successResponse(
            'Cliente actualizado correctamente.',
            new ShowClientResource($client->load(['vehicles' => fn($q) => $q->where('active', true)]))
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
}
