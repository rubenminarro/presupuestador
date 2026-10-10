<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConsumeWorkOrderPartRequest;
use App\Http\Requests\ReturnWorkOrderPartRequest;
use App\Http\Requests\StoreWorkOrderPartRequest;
use App\Http\Requests\UpdateWorkOrderPartRequest;
use App\Http\Resources\WorkOrderPartResource;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use App\Services\WorkOrderPartService;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WorkOrderPartController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public function __construct(
        protected WorkOrderPartService $workOrderPartService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:work-order-parts.index', only: ['index']),
            new Middleware('permission:work-order-parts.store', only: ['store']),
            new Middleware('permission:work-order-parts.update', only: ['update']),
            new Middleware('permission:work-order-parts.reserve', only: ['reserve']),
            new Middleware('permission:work-order-parts.consume', only: ['consume']),
            new Middleware('permission:work-order-parts.return', only: ['returnToStock']),
            new Middleware('permission:work-order-parts.cancel', only: ['cancel']),
        ];
    }

    public function index(WorkOrder $workOrder)
    {
        $parts = $workOrder->parts()->with('part')->orderBy('id')->get();

        return $this->successResponse(
            'Repuestos de la orden de trabajo obtenidos correctamente.',
            WorkOrderPartResource::collection($parts),
            200
        );
    }

    public function store(StoreWorkOrderPartRequest $request, WorkOrder $workOrder)
    {
        $line = $this->workOrderPartService->add(
            $workOrder,
            $request->validated(),
            $request->user()
        );

        return $this->successResponse(
            'Repuesto agregado a la orden de trabajo correctamente.',
            new WorkOrderPartResource($line),
            201
        );
    }

    public function update(UpdateWorkOrderPartRequest $request, WorkOrder $workOrder, WorkOrderPart $part)
    {
        $line = $this->workOrderPartService->update($workOrder, $part, $request->validated());

        return $this->successResponse(
            'Repuesto de la orden de trabajo actualizado correctamente.',
            new WorkOrderPartResource($line),
            200
        );
    }

    public function reserve(WorkOrder $workOrder, WorkOrderPart $part)
    {
        $line = $this->workOrderPartService->reserve($workOrder, $part);

        return $this->successResponse(
            'Stock del repuesto reservado correctamente.',
            new WorkOrderPartResource($line),
            200
        );
    }

    public function consume(ConsumeWorkOrderPartRequest $request, WorkOrder $workOrder, WorkOrderPart $part)
    {
        $line = $this->workOrderPartService->consume(
            $workOrder,
            $part,
            $request->validated(),
            $request->user()
        );

        return $this->successResponse(
            'Consumo del repuesto registrado correctamente.',
            new WorkOrderPartResource($line),
            200
        );
    }

    public function returnToStock(ReturnWorkOrderPartRequest $request, WorkOrder $workOrder, WorkOrderPart $part)
    {
        $line = $this->workOrderPartService->returnToStock(
            $workOrder,
            $part,
            $request->validated(),
            $request->user()
        );

        return $this->successResponse(
            'Devolución del repuesto registrada correctamente.',
            new WorkOrderPartResource($line),
            200
        );
    }

    public function cancel(WorkOrder $workOrder, WorkOrderPart $part)
    {
        $line = $this->workOrderPartService->cancel($workOrder, $part);

        return $this->successResponse(
            'Repuesto de la orden de trabajo cancelado correctamente.',
            new WorkOrderPartResource($line),
            200
        );
    }
}
