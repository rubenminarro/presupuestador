<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;
use App\Http\Resources\UserResource;


class AuthController extends Controller
{
    
    use ApiResponse;
    
    public function login(LoginRequest $request)
    {
        
         if (!Auth::attempt($request->validated())) {
            return $this->errorResponse('Credenciales inválidas.', [
                'email' => ['Email incorrecto.'],
                'password' => ['Contraseña incorrecta.']
            ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse(
            'Inicio de sesión exitoso.',
            [
                'token' => $token,
                'user'  => new UserResource($user->load('roles'))
            ]
        );
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        auth()->guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return $this->successResponse('Sesión cerrada correctamente.');
    }
}
