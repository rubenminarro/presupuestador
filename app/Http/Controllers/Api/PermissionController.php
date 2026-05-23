<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Http\Resources\PermissionsResource;
use App\Http\Resources\ShowPermissionResource;
use App\Models\Permission;
use App\Traits\ApiResponse;

class PermissionController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        
        $permissions = Permission::when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        })
        ->latest()
        ->paginate(
            $request->per_page ?? 10
        );
    
        return $this->successResponse(
            'Permisos obtenidos correctamente.',
            PermissionsResource::collection($permissions->items()),
            200,
            [
                'pagination' => [
                    'total'       => $permissions->total(),
                    'perPage'     => $permissions->perPage(),
                    'currentPage' => $permissions->currentPage(),
                    'lastPage'    => $permissions->lastPage(),
                ]
            ]
        );
    }

    public function store(StorePermissionRequest $request)
    {
        
        $data = $request->validated();

        $permission = Permission::create($data);
    
        return $this->successResponse(
            'Permiso creado correctamente.', 
            new ShowPermissionResource($permission), 
            201
        );
    }

    public function show(Permission $permission)
    {
        return $this->successResponse(
            'Permiso encontrado.', 
            new ShowPermissionResource($permission), 
            200
        );
    }

    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        
        $data = $request->validated();

        $permission->update($data);

        return $this->successResponse(
            'Permiso actualizado correctamente.', 
            new ShowPermissionResource($permission), 
            200
        );
    
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        
        return $this->successResponse(
            'Permiso eliminado correctamente.',
            null,
            200
        );
    }
}
