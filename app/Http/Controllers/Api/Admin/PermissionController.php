<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Http\Resources\ShowPermissionResource;
use App\Http\Resources\PermissionsResource;
use App\Models\Permission;
use App\Traits\ApiResponse;

class PermissionController extends Controller
{
    
    use ApiResponse;

    public function index(Request $request)
    {
        
        $search = $request->query('search');
        
        $permissions = Permission::when($search, function ($query, $search) {
            $query->where('name', 'like', "%{$search}%");
        })->orderBy('name')->paginate(10);

        $data = PermissionsResource::collection($permissions->items());
    
        return $this->successResponse(
            'Permisos obtenidos correctamente.',
            $data,
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
    
        return $this->successResponse('Permiso creado correctamente.', new ShowPermissionResource($permission), 201);
    }

    public function show(Permission $permission)
    {
        return $this->successResponse('Permiso encontrado.', new  ShowPermissionResource($permission));
    }

    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        
        $data = $request->validated();

        $permission->update($data);

        return $this->successResponse('Permiso actualizado correctamente.', new ShowPermissionResource($permission));
    
    }

    public function destroy(Permission $permission)
    {
        if (!$permission->canBeDeleted()) {

            return $this->errorResponse('Hubo un error al eliminar el permiso.', ['name' => ['Este permiso está en uso. Desactívalo en lugar de borrarlo.']], 422);

        }

        $permission->delete();
        
        return $this->successResponse('Permiso eliminado correctamente.');
        
    }

    public function activate(Permission $permission)
    {
        $permission->update(['active' => !$permission->active]);
        
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $data = [
            'id' => $permission->id,
            'active' => $permission->active,
        ];

        $message = $permission->active ? 'Permiso activado correctamente.' : 'Permiso desactivado correctamente.';

        return $this->successResponse($message, $data);
    }
}
