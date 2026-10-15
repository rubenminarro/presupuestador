<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceCategoryResource;
use App\Models\ServiceCategory;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ServiceCategoryController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:service-categories.index', only: ['index']),
        ];
    }

    public function index()
    {
        $categories = ServiceCategory::query()
            ->withCount('services')
            ->orderBy('name')
            ->get();

        return $this->successResponse(
            'Categorías de servicio obtenidas correctamente.',
            ServiceCategoryResource::collection($categories),
            200
        );
    }
}
