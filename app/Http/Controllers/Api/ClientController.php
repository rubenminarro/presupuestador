<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\ShowClientResource;
use App\Models\Client;
use App\Traits\ApiResponse;

class ClientController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        
        $clients = Client::query()
        ->when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;
            
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->when($request->filled('document_number'), function ($query) use ($request) {
            $query->where('document_number', 'like', "%{$request->document_number}%");
        })
        ->when($request->filled('first_name'), function ($query) use ($request) {
            $query->where('first_name', 'like', "%{$request->first_name}%");
        })
        ->when($request->filled('last_name'), function ($query) use ($request) {
            $query->where('last_name', 'like', "%{$request->last_name}%");
        })
        ->when($request->filled('phone'), function ($query) use ($request) {
            $query->where('phone', 'like', "%{$request->phone}%");
        })
        ->when($request->filled('email'), function ($query) use ($request) {
            $query->where('email', 'like', "%{$request->email}%");
        })
        ->latest()
        ->paginate(
            $request->per_page ?? 10
        );

        return $this->successResponse(
            'Clientes obtenidos correctamente.',
            ClientResource::collection($clients->items()),
            200,
            [
                'pagination' => [
                    'total'       => $clients->total(),
                    'perPage'     => $clients->perPage(),
                    'currentPage' => $clients->currentPage(),
                    'lastPage'    => $clients->lastPage(),
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
        
        $client->loadMissing([
            'vehicles.brand', 
            'vehicles.vehicleModel'
        ]);
    
        return $this->successResponse(
            'Cliente encontrado.',
            new ShowClientResource($client),
            200
        );
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $data = $request->validated();

        $client->update($data);

        $client->loadMissing([
            'vehicles.brand', 
            'vehicles.vehicleModel'
        ]);

        return $this->successResponse(
            'Cliente actualizado correctamente.',
            new ShowClientResource($client),
            200
        );
    }

    public function destroy(Client $client)
    {
        
        $suffix = '//deleted_' . now()->timestamp;

        if ($client->document_number) {
            $client->document_number = substr($client->document_number . $suffix, 0, 30);
        }

        if ($client->email) {
            $client->email = $client->email . $suffix;
        }

        $client->save();

        $client->delete();

        return $this->successResponse(
            'Cliente eliminado correctamente.',
            null,
            200
        );
    }
}
