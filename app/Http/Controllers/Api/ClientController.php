<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ShowClientResource;
use App\Models\Client;
use App\Traits\ApiResponse;

class ClientController extends Controller
{
    
    use ApiResponse;

    public function index(Request $request)
    {
        
        $search = $request->query('search');

        $clients = Client::when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
            });
        })->orderBy('last_name')->paginate(10);

        return $this->successResponse(
            'Clientes obtenidos correctamente.',
            [
                'items' => ShowClientResource::collection($clients->items()),
                'pagination' => [
                    'current_page' => $clients->currentPage(),
                    'last_page' => $clients->lastPage(),
                    'per_page' => $clients->perPage(),
                    'total' => $clients->total(),
                ]
            ]
        );
    }
    
    public function store(StoreClientRequest $request)
    {
        $data = $request->validated();

        $client = Client::create($data);
            
        return $this->successResponse(
            'Cliente creado correctamente.',
            new ShowClientResource($client),
            201
        );
    }

    public function show(Client $client)
    {
        return $this->successResponse(
            'Cliente encontrado.', 
            new ShowClientResource(
                $client->load([
                    'vehicles' => fn ($q) => $q->where('active', true)
                ])
            ));
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $data = $request->validated();

        $client->update($data);

        return $this->successResponse(
            'Cliente actualizado correctamente.',
            new ShowClientResource($client)
        );
    }

    public function activate(Client $client)
    {
        $client->update([
            'active' => !$client->active
        ]);

        return $this->successResponse(
            $client->active
                ? 'Cliente activado correctamente.'
                : 'Cliente desactivado correctamente.',
            [
                'id' => $client->id,
                'active' => $client->active
            ]
        );
    }
}
