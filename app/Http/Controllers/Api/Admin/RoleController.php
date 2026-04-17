<?php

namespace App\Http\Controllers\Api\Admin;

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
        $search = $request->query('search');

        $roles = Role::when($search, function ($query, $search) {
            $query->where('name', 'like', "%{$search}%");
        })->orderBy('name')->paginate(10);

        $data = RolesResource::collection($roles->items());
    
        return $this->successResponse(
            'Roles obtenidos correctamente.',
            $data,
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
        return $this->successResponse('Rol encontrado.', new ShowRoleResource($role));
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
            new ShowRoleResource($role)
        );
    }

    public function activate(Role $role)
    {
        $role->update(['active' => !$role->active]);

        $data = [
            'id' => $role->id,
            'active' => $role->active,
        ];

        $message = $role->active ? 'Rol activado correctamente.' : 'Rol desactivado correctamente.';

        return $this->successResponse($message, $data);
    }

    public function destroy(Role $role)
    {
        
        if (!$role->canBeDeleted()) {

            return $this->errorResponse('Hubo un error al eliminar el rol.', ['name' => ['Este rol está en uso. Desactívalo en lugar de borrarlo.']], 422);

        }
        
        $role->delete();
        
        return $this->successResponse('Rol eliminado correctamente.');
    }

    public function permissions()
    {
        $permissions = Permission::all()
        ->groupBy(fn($p) => explode('.', $p->name)[0])
        ->map(function ($group, $module) {
            return [
                'module' => $module,
                'list'   => $group->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->values()
            ];
        })->values();

        return $this->successResponse(
            'Permisos obtenidos correctamente.',
            $permissions,
            200
        );
    }
}
