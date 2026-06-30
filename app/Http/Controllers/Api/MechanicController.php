<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMechanicRequest;
use App\Http\Requests\UpdateMechanicRequest;
use App\Http\Resources\MechanicResource;
use App\Http\Resources\ShowMechanicResource;
use App\Models\Mechanic;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MechanicController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $search = $request->search;

        $query = Mechanic::query()
            ->with('user');

        $query->when($request->filled('employee_code'), function ($q) use ($request) {
            $q->where('employee_code', 'like', "%{$request->employee_code}%");
        });
        
        $query->when($request->filled('specialty'), function ($q) use ($request) {
            $q->where('specialty', 'like', "%{$request->specialty}%");
        });
        
        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->status);
        });

        $query->when($request->filled('user_name'), function ($q) use ($request) {
            $q->whereHas('user', function ($u) use ($request) {
                $u->where('name', 'like', "%{$request->user_name}%");
            });
        });

        $query->when($request->filled('user_first_name'), function ($q) use ($request) {
            $q->whereHas('user', function ($u) use ($request) {
                $u->where('first_name', 'like', "%{$request->user_first_name}%");
            });
        });

        $query->when($request->filled('user_last_name'), function ($q) use ($request) {
            $q->whereHas('user', function ($u) use ($request) {
                $u->where('last_name', 'like', "%{$request->user_last_name}%");
            });
        });

        $query->when($request->filled('user_email'), function ($q) use ($request) {
            $q->whereHas('user', function ($u) use ($request) {
                $u->where('email', 'like', "%{$request->user_email}%");
            });
        });

        $query->when($request->filled('search'), function ($q) use ($search) {
            $q->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('employee_code', 'like', "%{$search}%")
                    ->orWhere('specialty', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        });

        $mechanics = $query
            ->latest()
            ->paginate($request->per_page ?? 10);

        return $this->successResponse(
            'Mecánicos obtenidos correctamente.',
            MechanicResource::collection($mechanics->items()),
            200,
            [
                'pagination' => [
                    'total' => $mechanics->total(),
                    'perPage' => $mechanics->perPage(),
                    'currentPage' => $mechanics->currentPage(),
                    'lastPage' => $mechanics->lastPage(),
                ]
            ]
        );
    }

    public function store(StoreMechanicRequest $request)
    {
        
        $data = $request->validated();

        $data['status'] = $request->status ?? 'active';

        $mechanic = DB::transaction(function () use ($data) {
            return Mechanic::create($data);
        });

        return $this->successResponse(
            'Mecánico creado correctamente.',
            new ShowMechanicResource(
                $mechanic->fresh()->load([
                    'user',
                ])
            ),
            201
        );
    }

    public function show(Mechanic $mechanic)
    {
        return $this->successResponse(
            'Mecánico encontrado.',
            new ShowMechanicResource(
                $mechanic->load('user')
            ),
            200
        );
    }

    public function update(UpdateMechanicRequest $request, Mechanic $mechanic) 
    {

        $data = $request->validated();
    
        DB::transaction(function () use (
            $mechanic,
            $data
        ) {

            return $mechanic->update($data);
        });

        return $this->successResponse(
            'Mecánico actualizado correctamente.',
            new ShowMechanicResource(
                $mechanic->fresh()->load('user')
            )
        );
    }

    public function destroy(Mechanic $mechanic)
    {
        $mechanic->delete();

        return $this->successResponse(
            'Mecánico eliminado correctamente.',
            null,
            200
        );
    }
}