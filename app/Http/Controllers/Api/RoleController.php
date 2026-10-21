<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\ShowRoleResource;
use App\Http\Resources\RolesResource;
use App\Models\Role;
use App\Models\Permission;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RoleController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:roles.index', only: ['index']),
            new Middleware('permission:roles.store', only: ['store']),
            new Middleware('permission:roles.show', only: ['show']),
            new Middleware('permission:roles.update', only: ['update']),
            new Middleware('permission:roles.destroy', only: ['destroy']),
            new Middleware('permission:roles.permissions-grouped-by-module', only: ['permissionsGroupedByModule']),
        ];
    }

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

    public function store(StoreRoleRequest $request, AuditLogger $auditLogger)
    {
        $data = $request->validated();

        $role = Role::create($data);

        $this->syncPermissions($role, $data['permissions'], $auditLogger);

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

    public function update(UpdateRoleRequest $request, Role $role, AuditLogger $auditLogger)
    {

        $data = $request->validated();

        $role->update($data);

        if (array_key_exists('permissions', $data)) {
            $this->syncPermissions($role, $data['permissions'], $auditLogger);
        }

        return $this->successResponse(
            'Rol actualizado correctamente.',
            new ShowRoleResource($role),
            200
        );
    }

    /**
     * syncPermissions no dispara eventos de modelo: se audita aparte.
     */
    private function syncPermissions(Role $role, array $permissions, AuditLogger $auditLogger): void
    {
        $before = $role->permissions()->pluck('name')->sort()->values()->all();

        $role->syncPermissions($permissions);

        $after = $role->permissions()->pluck('name')->sort()->values()->all();

        if ($before !== $after) {
            $auditLogger->record(AuditEvent::PERMISSIONS_SYNCED, $role, ['permissions' => $before], ['permissions' => $after]);
        }
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
