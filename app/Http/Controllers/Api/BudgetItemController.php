<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBudgetItemRequest;
use App\Http\Requests\UpdateBudgetItemRequest;
use App\Http\Resources\BudgetItemResource;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Services\BudgetService;
use App\Traits\ApiResponse;

class BudgetItemController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected BudgetService $budgetService
    ) {
    }

    public function index(Budget $budget)
    {
        $items = $budget->items()->with('part')->latest()->get();

        return $this->successResponse(
            'Items del presupuesto obtenidos exitosamente.',
            BudgetItemResource::collection($items)
        );
    }

    public function store(StoreBudgetItemRequest $request, Budget $budget) 
    {

        $this->budgetService->ensureEditable($budget);

        $data = $this->budgetService->preparePartItem($request->validated());

        $data['total'] = $data['quantity'] * $data['unit_price'];

        $item = $budget->items()->create($data);

        $this->budgetService->recalculateBudget($budget);

        return $this->successResponse(
            'El item del presupuesto creado exitosamente.',
            new BudgetItemResource($item->load('part')),
            201
        );
    }

    public function show(Budget $budget, BudgetItem $item)
    {
        
        return $this->successResponse(
            'El item del presupuesto recuperado exitosamente.',
            new BudgetItemResource($item->load('part'))
        );
    }

    public function update(UpdateBudgetItemRequest $request, Budget $budget, BudgetItem $item) 
    {
        
        $this->budgetService->ensureEditable($budget);

        $data = $this->budgetService->preparePartItem($request->validated(), $item);

        $quantity = $data['quantity'] ?? $item->quantity;
        $unitPrice = $data['unit_price'] ?? $item->unit_price;

        $data['total'] = $quantity * $unitPrice;

        $item->update($data);

        $this->budgetService->recalculateBudget($budget);

        return $this->successResponse(
            'El item del presupuesto actualizado exitosamente.',
            new BudgetItemResource($item->refresh()->load('part'))
        );
    }

    public function destroy(Budget $budget, BudgetItem $item) 
    {
        $this->budgetService->ensureEditable($budget);

        $item->delete();

        $this->budgetService->recalculateBudget($budget);

        return $this->successResponse(
            'El item del presupuesto eliminado exitosamente.',
            null
        );
    }

}
