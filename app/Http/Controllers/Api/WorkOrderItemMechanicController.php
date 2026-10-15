<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LogWorkOrderItemHoursRequest;
use App\Http\Requests\SyncWorkOrderItemMechanicsRequest;
use App\Http\Resources\WorkOrderItemMechanicResource;
use App\Models\Mechanic;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\WorkOrderCommissionService;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Mecánicos asignados a un trabajo (labor/service) de la OT.
 */
class WorkOrderItemMechanicController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public function __construct(
        protected WorkOrderCommissionService $commissionService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:work-order-item-mechanics.index', only: ['index']),
            new Middleware('permission:work-order-item-mechanics.sync', only: ['sync']),
            new Middleware('permission:work-order-item-mechanics.log-hours', only: ['logHours']),
        ];
    }

    /**
     * Horas reales de un mecánico asignado. Sin permiso "log-hours-any",
     * cada mecánico solo carga las propias.
     */
    public function logHours(
        LogWorkOrderItemHoursRequest $request,
        WorkOrder $workOrder,
        WorkOrderItem $item,
        Mechanic $mechanic
    ) {
        if (
            !$request->user()->can('work-order-item-mechanics.log-hours-any') &&
            (int) $mechanic->user_id !== (int) $request->user()->id
        ) {
            return $this->errorResponse(
                'Solo puede cargar sus propias horas.',
                403,
                'FORBIDDEN'
            );
        }

        $assignment = $this->commissionService->logHours(
            $workOrder,
            $item,
            $mechanic,
            $request->validated('hours')
        );

        return $this->successResponse(
            'Horas del trabajo registradas correctamente.',
            new WorkOrderItemMechanicResource($assignment),
            200
        );
    }

    public function index(WorkOrder $workOrder, WorkOrderItem $item)
    {
        $item->load(['budgetItem', 'mechanicAssignments.mechanic.user']);

        return $this->successResponse(
            'Mecánicos del trabajo obtenidos correctamente.',
            WorkOrderItemMechanicResource::collection($item->mechanicAssignments),
            200
        );
    }

    public function sync(SyncWorkOrderItemMechanicsRequest $request, WorkOrder $workOrder, WorkOrderItem $item)
    {
        $item = $this->commissionService->syncItemMechanics(
            $workOrder,
            $item,
            $request->validated('mechanics')
        );

        return $this->successResponse(
            'Mecánicos del trabajo actualizados correctamente.',
            WorkOrderItemMechanicResource::collection($item->mechanicAssignments),
            200
        );
    }
}
