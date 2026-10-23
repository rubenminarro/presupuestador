<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\ShowUserResource;
use App\Http\Resources\UsersResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserRoleController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:users.index', only: ['index']),
            new Middleware('permission:users.store', only: ['store']),
            new Middleware('permission:users.show', only: ['show']),
            new Middleware('permission:users.update', only: ['update']),
            new Middleware('permission:users.destroy', only: ['destroy']),
        ];
    }
    
    public function index(Request $request)
    {
        
        $users = User::query()
        ->with(['roles'])
        ->when($request->filled('search'), function ($query) use ($request) {
            
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereHas('roles', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                    }
                );
            });
        })
        ->when($request->filled('name'), function ($query) use ($request) {
            $query->where('name', 'like', "%{$request->name}%");
        })
        ->when($request->filled('email'), function ($query) use ($request) {
            $query->where('email', 'like', "%{$request->email}%");
        })
        ->when($request->filled('first_name'), function ($query) use ($request) {
            $query->where('first_name', 'like', "%{$request->first_name}%");
        })
        ->when($request->filled('last_name'), function ($query) use ($request) {
            $query->where('last_name', 'like', "%{$request->last_name}%");
        })
        ->when($request->filled('role_name'), function ($query) use ($request) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->role_name}%");
            });
        })
        ->when($request->filled('role_description'), function ($query) use ($request) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('description', 'like', "%{$request->role_description}%");
            });
        })
        ->latest()
        ->paginate(
            $request->per_page ?? 10
        );

        return $this->successResponse(
            'Usuarios obtenidos correctamente.',
            UsersResource::collection($users->items()),
            200,
            [
                'pagination' => [
                    'total'       => $users->total(),
                    'perPage'     => $users->perPage(),
                    'currentPage' => $users->currentPage(),
                    'lastPage'    => $users->lastPage(),
                ]
            ]
        );

    }

    public function store(StoreUserRequest $request, AuditLogger $auditLogger)
    {
        $data = $request->validated();
        $data['password'] = bcrypt($request->password);

        $user = User::create($data);

        $user->assignRole($request->role);

        $auditLogger->record(AuditEvent::ROLES_SYNCED, $user, ['roles' => []], ['roles' => $user->getRoleNames()->values()->all()]);
    
        return $this->successResponse(
            'Usuario creado correctamente.', 
            new ShowUserResource($user),
            201
        );
        
    }

    public function show(User $user)
    {
        return $this->successResponse(
            'Usuario encontrado.', 
            new ShowUserResource($user),
            200
        );
    }

    public function update(UpdateUserRequest $request, User $user, AuditLogger $auditLogger)
    {
        $data = $request->validated();

        // Cambiarse el propio rol puede dejar sin acceso a la administración: lo hace otro administrador.
        if (
            array_key_exists('role', $data) &&
            $user->is($request->user()) &&
            !$user->hasRole($data['role'])
        ) {
            return $this->errorResponse(
                'No puede cambiar su propio rol.',
                422,
                'USER_SELF_ACTION'
            );
        }

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if (array_key_exists('role', $data)) {
            $before = $user->getRoleNames()->sort()->values()->all();

            $user->syncRoles([$data['role']]);

            $after = $user->load('roles')->getRoleNames()->sort()->values()->all();

            // syncRoles no dispara eventos de modelo: se audita aparte.
            if ($before !== $after) {
                $auditLogger->record(AuditEvent::ROLES_SYNCED, $user, ['roles' => $before], ['roles' => $after]);
            }
        }

        return $this->successResponse(
            'Usuario actualizado correctamente.',
            new ShowUserResource($user),
            200
        );
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return $this->errorResponse(
                'No puede eliminar su propio usuario.',
                422,
                'USER_SELF_ACTION'
            );
        }

        $suffix = '//deleted_' . now()->timestamp;

        if ($user->name) {
            $user->name = substr($user->name . $suffix, 0, 30);
        }

        if ($user->email) {
            $user->email = $user->email . $suffix;
        }

        $user->save();
    
        $user->delete();

        return $this->successResponse(
            'Usuario eliminado correctamente.', 
            null, 
            200
        );

    }

}
