<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Http\Resources\ShowWorkOrderResource;
use App\Services\WorkOrderService;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WorkOrderItemController extends Controller implements HasMiddleware
{
    
    use ApiResponse;

    public function __construct(
        protected WorkOrderService $workOrderService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:work-orders.start', only: ['start']),
            new Middleware('permission:work-orders.complete', only: ['complete']),
            new Middleware('permission:work-orders.cancel', only: ['cancel']),
        ];
    }

    public function start( WorkOrder $workOrder, WorkOrderItem $item ) {

        $this->workOrderService->startItem( $workOrder, $item );

        $workOrder = $this->loadWorkOrderRelations($workOrder);

        return $this->successResponse(
            'Item de orden de trabajo iniciado correctamente.', 
            new ShowWorkOrderResource($workOrder), 
            200
        );
        
    }

    public function complete( WorkOrder $workOrder, WorkOrderItem $item ) {

        $this->workOrderService->completeItem( $workOrder, $item );

        $workOrder = $this->loadWorkOrderRelations($workOrder);

        return $this->successResponse(
            'Item de orden de trabajo completado correctamente.', 
            new ShowWorkOrderResource($workOrder), 
            200
        );
    }

    public function cancel( WorkOrder $workOrder, WorkOrderItem $item ) {

        $this->workOrderService->cancelItem( $workOrder, $item );

        $workOrder = $this->loadWorkOrderRelations($workOrder);

        return $this->successResponse(
            'Item de orden de trabajo cancelado correctamente.', 
            new ShowWorkOrderResource($workOrder), 
            200 
        );
    }

    private function loadWorkOrderRelations(WorkOrder $workOrder): WorkOrder
    {
        return $workOrder->load([
            'reception',
            'reception.client',
            'reception.vehicle',
            'budget',
            'mechanic',
            'mechanic.user',
            'creator',
            'items',
            'items.budgetItem',
        ]);
    }
}
