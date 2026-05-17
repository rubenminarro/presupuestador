<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $budgets = Budget::query()
            ->with([
                'items',
                'reception',
            ])
            ->latest()
            ->paginate(10);

        return $this->successResponse(
            BudgetResource::collection($budgets),
            'Presupuestos recuperados exitosamente.'
        );
    }

    public function store(StoreBudgetRequest $request)
    {
        $budget = Budget::create([

            'reception_id' => $request->reception_id,

            'created_by' => Auth::id(),

            'code' => $this->generateCode(),

            'status' => 'draft',

            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,

            'notes' => $request->notes,

        ]);

        $budget->load([
            'items',
            'reception',
        ]);

        return $this->successResponse(
            new BudgetResource($budget),
            'Presupuesto creado exitosamente.',
            201
        );
    }

    public function show(Budget $budget)
    {
        $budget->load([
            'items',
            'reception',
        ]);

        return $this->successResponse(
            new BudgetResource($budget),
            'Presupuesto recuperado exitosamente.'
        );
    }

    public function update(UpdateBudgetRequest $request, Budget $budget) 
    {

        $data = $request->validated();

        if (isset($data['status']) && $data['status'] === 'approved') {
            $data['approved_at'] = now();
        }

        $budget->update($data);

        $budget->load([
            'items',
            'reception',
        ]);

        return $this->successResponse(
            new BudgetResource($budget),
            'Presupuesto actualizado exitosamente.'
        );
    }

    public function destroy(Budget $budget)
    {
        $budget->delete();

        return $this->successResponse(
            null,
            'Presupuesto eliminado exitosamente.'
        );
    }

    private function generateCode(): string
    {
        $nextId = Budget::max('id') + 1;

        return 'BUD-' . str_pad(
            $nextId,
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}
