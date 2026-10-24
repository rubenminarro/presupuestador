<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserPreferencesRequest;
use App\Services\UserPreferenceService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * Preferencias visuales del usuario autenticado. Sin permisos: cada usuario
 * lee y cambia solo las suyas.
 */
class UserPreferenceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserPreferenceService $userPreferenceService
    ) {
    }

    public function show(Request $request)
    {
        return $this->successResponse(
            'Preferencias obtenidas correctamente.',
            $this->userPreferenceService->get($request->user()),
            200
        );
    }

    public function update(UpdateUserPreferencesRequest $request)
    {
        return $this->successResponse(
            'Preferencias guardadas correctamente.',
            $this->userPreferenceService->update($request->user(), $request->validated()),
            200
        );
    }

    public function destroy(Request $request)
    {
        return $this->successResponse(
            'Preferencias restauradas a los valores predeterminados.',
            $this->userPreferenceService->reset($request->user()),
            200
        );
    }
}
