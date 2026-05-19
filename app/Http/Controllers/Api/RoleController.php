<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RolesResource;
use App\Http\Resources\ShowRoleResource;
use App\Models\Role;
use App\Models\Permission;
use App\Traits\ApiResponse;

class RoleController extends Controller

{
    
    use ApiResponse;

    public function index(Request $request)
    {
        $roles = Role::when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        })
        ->latest()
        ->paginate(
            $request->per_page ?? 10
        );

        return $this->successResponse(
            'Roles obtenidos correctamente.',
            RolesResource::collection($roles->items()),
            200,
            [
                'pagination' => [
                    'total'       => $roles->total(),
                    'perPage'     => $roles->perPage(),
                    'currentPage' => $roles->currentPage(),
                    'lastPage'    => $roles->lastPage(),
                ]
            ]
        );
    }

    public function store(StoreRoleRequest $request)
    {
        $data = $request->validated();

        $role = Role::create($data);

        $role->syncPermissions($data['permissions']);

        return $this->successResponse(
            'Rol creado correctamente.',
            new ShowRoleResource($role),
            201
        );
    }

    public function show(Role $role)
    {
        return $this->successResponse(
            'Rol encontrado.', 
            new ShowRoleResource($role),
            200
        );
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        
        $data = $request->validated();

        $role->update($data);
    
        if (array_key_exists('permissions', $data)) {
            $role->syncPermissions($data['permissions']);
        }

        return $this->successResponse(
            'Rol actualizado correctamente.',
            new ShowRoleResource($role),
            200
        );
    }

    public function destroy(Role $role)
    {
        $role->delete();
        
        return $this->successResponse(
            'Rol eliminado correctamente.', 
            null, 
            200
        );
    }

    public function permissionsGroupedByModule()
    {
        $permissions = Permission::getGroupedByModule();

        return $this->successResponse(
            'Permisos obtenidos correctamente.',
            $permissions,
            200
        );
    }
}
