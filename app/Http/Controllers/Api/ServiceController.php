<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\ShowServiceResource;
use App\Models\Service;
use App\Services\ServiceCatalogService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ServiceController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public function __construct(
        protected ServiceCatalogService $serviceCatalogService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:services.index', only: ['index']),
            new Middleware('permission:services.store', only: ['store']),
            new Middleware('permission:services.show', only: ['show']),
            new Middleware('permission:services.update', only: ['update']),
            new Middleware('permission:services.destroy', only: ['destroy']),
            new Middleware('permission:services.change-status', only: ['activate', 'deactivate']),
        ];
    }

    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $query = Service::query()->with(['category']);

        $query->when($request->filled('search'), function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        });

        $query->when($request->filled('category_id'), function ($q) use ($request) {
            $q->where('service_category_id', $request->category_id);
        });

        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->status);
        });

        $services = $query
            ->orderBy('name')
            ->paginate($request->per_page ?? 10);

        return $this->successResponse(
            'Servicios obtenidos correctamente.',
            ServiceResource::collection($services->items()),
            200,
            [
                'pagination' => [
                    'total'       => $services->total(),
                    'perPage'     => $services->perPage(),
                    'currentPage' => $services->currentPage(),
                    'lastPage'    => $services->lastPage(),
                ]
            ]
        );
    }

    public function show(Service $service)
    {
        return $this->successResponse(
            'Servicio obtenido correctamente.',
            new ShowServiceResource($service->load('category')),
            200
        );
    }

    public function store(StoreServiceRequest $request)
    {
        $service = $this->serviceCatalogService->create($request->validated());

        return $this->successResponse(
            'Servicio creado correctamente.',
            new ShowServiceResource($service),
            201
        );
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {
        $service = $this->serviceCatalogService->update($service, $request->validated());

        return $this->successResponse(
            'Servicio actualizado correctamente.',
            new ShowServiceResource($service),
            200
        );
    }

    public function activate(Service $service)
    {
        $service = $this->serviceCatalogService->activate($service);

        return $this->successResponse(
            'Servicio activado correctamente.',
            new ShowServiceResource($service),
            200
        );
    }

    public function deactivate(Service $service)
    {
        $service = $this->serviceCatalogService->deactivate($service);

        return $this->successResponse(
            'Servicio desactivado correctamente.',
            new ShowServiceResource($service),
            200
        );
    }

    public function destroy(Service $service)
    {
        $this->serviceCatalogService->delete($service);

        return $this->successResponse(
            'Servicio eliminado correctamente.',
            null,
            200
        );
    }
}
