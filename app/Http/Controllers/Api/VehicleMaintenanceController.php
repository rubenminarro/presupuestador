<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfigureVehicleMaintenanceRequest;
use App\Http\Requests\IndexMaintenanceAlertRequest;
use App\Http\Requests\RecordVehicleMaintenanceRequest;
use App\Models\MaintenancePlan;
use App\Models\Vehicle;
use App\Services\MaintenanceService;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class VehicleMaintenanceController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public function __construct(
        protected MaintenanceService $maintenanceService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:vehicle-maintenances.index', only: ['index']),
            new Middleware('permission:vehicle-maintenances.record', only: ['record']),
            new Middleware('permission:vehicle-maintenances.configure', only: ['configure']),
            new Middleware('permission:maintenance-alerts.index', only: ['alerts']),
        ];
    }

    /**
     * Estado de cada plan en el vehículo y su kilometraje actual estimado.
     */
    public function index(Vehicle $vehicle)
    {
        return $this->successResponse(
            'Mantenimientos del vehículo obtenidos correctamente.',
            $this->maintenanceService->forVehicle($vehicle),
            200
        );
    }

    /**
     * Registro manual de un mantenimiento realizado.
     */
    public function record(RecordVehicleMaintenanceRequest $request, Vehicle $vehicle)
    {
        $plan = MaintenancePlan::findOrFail($request->validated('maintenance_plan_id'));

        $maintenance = $this->maintenanceService->recordDone($vehicle, $plan, $request->validated());

        return $this->successResponse(
            'Mantenimiento registrado correctamente.',
            $this->maintenanceService->present(
                $maintenance,
                $this->maintenanceService->estimatedMileage($vehicle)['mileage']
            ),
            201
        );
    }

    /**
     * Intervalos propios del vehículo para un plan.
     */
    public function configure(ConfigureVehicleMaintenanceRequest $request, Vehicle $vehicle, MaintenancePlan $maintenancePlan)
    {
        $maintenance = $this->maintenanceService->configure($vehicle, $maintenancePlan, $request->validated());

        return $this->successResponse(
            'Intervalos del vehículo actualizados correctamente.',
            $this->maintenanceService->present(
                $maintenance,
                $this->maintenanceService->estimatedMileage($vehicle)['mileage']
            ),
            200
        );
    }

    /**
     * Mantenimientos vencidos o por vencer de todos los vehículos.
     */
    public function alerts(IndexMaintenanceAlertRequest $request)
    {
        $alerts = $this->maintenanceService->alerts($request->status);
        $perPage = (int) ($request->per_page ?? 20);
        $page = (int) ($request->page ?? 1);

        return $this->successResponse(
            'Alertas de mantenimiento obtenidas correctamente.',
            $alerts->forPage($page, $perPage)->values(),
            200,
            [
                'pagination' => [
                    'total'       => $alerts->count(),
                    'perPage'     => $perPage,
                    'currentPage' => $page,
                    'lastPage'    => max(1, (int) ceil($alerts->count() / $perPage)),
                ],
                'counts' => [
                    'overdue' => $alerts->where('status.value', 'overdue')->count(),
                    'due_soon' => $alerts->where('status.value', 'due_soon')->count(),
                ],
            ]
        );
    }
}
