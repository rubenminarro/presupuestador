<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Http\Resources\ShowUserResource;
use App\Http\Resources\UsersResource;


class UserRoleController extends Controller
{
    use ApiResponse;
    
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

    public function show(User $user)
    {
        return $this->successResponse(
            'Usuario encontrado.', 
            new ShowUserResource($user),
            200
        );
    }

    public function store(StoreUserRequest $request, User $user)
    {
        $data = $request->validated();
        $data['password'] = bcrypt($request->password);

        $user = User::create($data);

        $user->assignRole($request->role);
    
        return $this->successResponse(
            'Usuario creado correctamente.', 
            new ShowUserResource($user),
            201
        );
        
    }

    public function update(UpdateUserRequest $request, User $user)
    {   
        
        $data = $request->validated();

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        $user->syncRoles([$request->role]);

        return $this->successResponse(
            'Usuario actualizado correctamente.',
            new ShowUserResource($user),
            200
        );

    }

    public function destroy(User $user)
    {
        $user->delete();

        return $this->successResponse(
            'Usuario eliminado correctamente.', 
            null, 
            200
        );

    }

}
