<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryAdjustmentRequest;
use App\Http\Requests\StoreInventoryEntryRequest;
use App\Http\Requests\StoreInventoryExitRequest;
use App\Http\Resources\ShowInventoryMovementResource;
use App\Services\InventoryService;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class InventoryOperationController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public function __construct(
        protected InventoryService $inventoryService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory.entry', only: ['storeEntry']),
            new Middleware('permission:inventory.exit', only: ['storeExit']),
            new Middleware('permission:inventory.adjust', only: ['storeAdjustment']),
        ];
    }

    public function storeEntry(StoreInventoryEntryRequest $request)
    {
        $movement = $this->inventoryService->registerEntry(
            $request->validated(),
            $request->user()
        );

        return $this->successResponse(
            'Entrada de stock registrada correctamente.',
            new ShowInventoryMovementResource($movement),
            201
        );
    }

    public function storeExit(StoreInventoryExitRequest $request)
    {
        $movement = $this->inventoryService->registerExit(
            $request->validated(),
            $request->user()
        );

        return $this->successResponse(
            'Salida de stock registrada correctamente.',
            new ShowInventoryMovementResource($movement),
            201
        );
    }

    public function storeAdjustment(StoreInventoryAdjustmentRequest $request)
    {
        $movement = $this->inventoryService->adjust(
            $request->validated(),
            $request->user()
        );

        return $this->successResponse(
            'Ajuste de stock registrado correctamente.',
            new ShowInventoryMovementResource($movement),
            201
        );
    }
}
