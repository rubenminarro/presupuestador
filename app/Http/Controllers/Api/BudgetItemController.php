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
        $items = $budget->items()->latest()->get();

        return $this->successResponse(
            BudgetItemResource::collection($items),
            'El item del presupuesto recuperado exitosamente.'
        );
    }

    public function store(StoreBudgetItemRequest $request, Budget $budget) {

        $data = $request->validated();

        $data['total'] =  $data['quantity'] * $data['unit_price'];

        $item = $budget->items()->create($data);

        $this->budgetService->recalculateBudget($budget);

        return $this->successResponse(
            new BudgetItemResource($item),
            'El item del presupuesto creado exitosamente.',
            201
        );
    }

    public function show(Budget $budget, BudgetItem $item)
    {
        
        if ($item->budget_id !== $budget->id) {
            return $this->errorResponse(
                'El item no pertenece a este presupuesto.',
                422
            );
        }

        return $this->successResponse(
            new BudgetItemResource($item),
            'El item del presupuesto recuperado exitosamente.'
        );
    }

    public function update(UpdateBudgetItemRequest $request, Budget $budget, BudgetItem $item) 
    {

        if ($item->budget_id !== $budget->id) {
            return $this->errorResponse(
                'El item no pertenece a este presupuesto.',
                422
            );
        }

        $data = $request->validated();

        $quantity = $data['quantity'] ?? $item->quantity;

        $unitPrice = $data['unit_price'] ?? $item->unit_price;

        $data['total'] = $quantity * $unitPrice;

        $item->update($data);

        $this->budgetService->recalculateBudget($budget);

        return $this->successResponse(
            new BudgetItemResource($item),
            'El item del presupuesto actualizado exitosamente.'
        );
    }

    public function destroy(Budget $budget, BudgetItem $item) 
    {

       if ($item->budget_id !== $budget->id) {

            return $this->errorResponse(
                'El item no pertenece a este presupuesto.',
                422
            );
        }

        $item->delete();

        $this->budgetService->recalculateBudget($budget);

        return $this->successResponse(
            null,
            'El item del presupuesto eliminado exitosamente.'
        );
    }
}
