<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadSystemLogoRequest;
use App\Services\BrandingService;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Configuración general del sistema. Por ahora, el logo: lectura pública
 * (branding, la usa el login) y cambio solo para el administrador.
 */
class SystemSettingController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public function __construct(
        private readonly BrandingService $brandingService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:system-settings.update', only: ['uploadLogo', 'deleteLogo']),
        ];
    }

    public function branding()
    {
        return $this->successResponse(
            'Identidad del sistema obtenida correctamente.',
            $this->brandingService->publicData(),
            200
        );
    }

    public function uploadLogo(UploadSystemLogoRequest $request)
    {
        return $this->successResponse(
            'Logo actualizado correctamente.',
            $this->brandingService->storeLogo($request->file('logo')),
            200
        );
    }

    public function deleteLogo()
    {
        return $this->successResponse(
            'Logo eliminado correctamente.',
            $this->brandingService->deleteLogo(),
            200
        );
    }
}
