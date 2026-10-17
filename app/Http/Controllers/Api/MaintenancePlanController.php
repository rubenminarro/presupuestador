<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenancePlanRequest;
use App\Http\Requests\UpdateMaintenancePlanRequest;
use App\Http\Resources\MaintenancePlanResource;
use App\Models\MaintenancePlan;
use App\Services\MaintenanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MaintenancePlanController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public function __construct(
        protected MaintenanceService $maintenanceService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:maintenance-plans.index', only: ['index']),
            new Middleware('permission:maintenance-plans.show', only: ['show']),
            new Middleware('permission:maintenance-plans.store', only: ['store']),
            new Middleware('permission:maintenance-plans.update', only: ['update']),
            new Middleware('permission:maintenance-plans.destroy', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $plans = MaintenancePlan::query()
            ->with('service')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . trim($request->search) . '%'))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->get();

        return $this->successResponse(
            'Planes de mantenimiento obtenidos correctamente.',
            MaintenancePlanResource::collection($plans),
            200
        );
    }

    public function show(MaintenancePlan $maintenancePlan)
    {
        return $this->successResponse(
            'Plan de mantenimiento obtenido correctamente.',
            new MaintenancePlanResource($maintenancePlan->load('service')),
            200
        );
    }

    public function store(StoreMaintenancePlanRequest $request)
    {
        $plan = $this->maintenanceService->createPlan($request->validated());

        return $this->successResponse(
            'Plan de mantenimiento creado correctamente.',
            new MaintenancePlanResource($plan->load('service')),
            201
        );
    }

    public function update(UpdateMaintenancePlanRequest $request, MaintenancePlan $maintenancePlan)
    {
        $plan = $this->maintenanceService->updatePlan($maintenancePlan, $request->validated());

        return $this->successResponse(
            'Plan de mantenimiento actualizado correctamente.',
            new MaintenancePlanResource($plan->load('service')),
            200
        );
    }

    public function destroy(MaintenancePlan $maintenancePlan)
    {
        $this->maintenanceService->deletePlan($maintenancePlan);

        return $this->successResponse(
            'Plan de mantenimiento eliminado correctamente.',
            null,
            200
        );
    }
}
