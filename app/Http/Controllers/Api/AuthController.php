<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;
use App\Http\Resources\UserResource;


class AuthController extends Controller
{
    
    use ApiResponse;
    
    public function login(LoginRequest $request, AuditLogger $auditLogger)
    {
        if (!Auth::attempt($request->validated())) {
            // Sin usuario: se guarda el email intentado.
            $auditLogger->record(AuditEvent::LOGIN_FAILED, null, null, ['email' => $request->input('email')]);

            return $this->errorResponse(
                'Credenciales inválidas.',
                401,
                'UNAUTHENTICATED',
                [
                    'email' => ['Email incorrecto.'],
                ]
            );
        }

        $user  = Auth::user();

        $token = $user->createToken('auth_token')->plainTextToken;

        $auditLogger->record(AuditEvent::LOGIN, $user, null, null, $user);

        return $this->successResponse(
            'Inicio de sesión exitoso.',
            [
                'token' => $token,
                'user'  => new UserResource($user->load('roles'))
            ]
        );
    }

    public function me(Request $request)
    {
        $user = $request->user();

        // El administrador pasa todo por Gate::before: se informan todos los permisos aunque su rol no los tenga.
        $permissions = $user->hasRole('administrador')
            ? Permission::where('guard_name', 'api')->pluck('name')
            : $user->getAllPermissions()->pluck('name');

        return $this->successResponse(
            'Usuario autenticado.',
            [
                'user'        => new UserResource($user->load('roles')),
                'permissions' => $permissions->sort()->values(),
            ]
        );
    }

    public function logout(Request $request, AuditLogger $auditLogger)
    {
        $auditLogger->record(AuditEvent::LOGOUT, $request->user());

        $request->user()->currentAccessToken()->delete();

        return $this->successResponse('Sesión cerrada correctamente.');
    }
}
