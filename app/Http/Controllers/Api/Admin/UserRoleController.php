<?php

namespace App\Http\Controllers\Api\Admin;

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
        
        $search = $request->query('search');

        $users = User::with('roles')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereHas('roles', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->paginate(10)
        ;

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
        return $this->successResponse('Usuario encontrado.', new ShowUserResource($user));
    }

    public function store(StoreUserRequest $request, User $user)
    {
        $data = $request->validated();
        $data['password'] = bcrypt($request->password);

        $user = User::create($data);

        $user->assignRole($request->role);
    
        return $this->successResponse('Usuario creado correctamente.', new ShowUserResource($user));
        
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

        return $this->successResponse('Usuario actualizado correctamente.', new ShowUserResource($user));

    }

    public function activate(User $user)
    {
        $user->update(['active' => !$user->active]);

        $data = [
            'id' => $user->id,
            'active' => $user->active,
        ];

        $message = $user->active ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.';

        return $this->successResponse($message, $data);

    }

}
