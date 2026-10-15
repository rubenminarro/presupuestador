<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexMechanicCommissionRequest;
use App\Models\Mechanic;
use App\Services\WorkOrderCommissionService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Comisiones liquidables de un mecánico en un período (por defecto, el mes en curso).
 */
class MechanicCommissionController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public function __construct(
        protected WorkOrderCommissionService $commissionService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:mechanic-commissions.index', only: ['index']),
        ];
    }

    public function index(IndexMechanicCommissionRequest $request, Mechanic $mechanic)
    {
        // Sin permiso para ver todas, cada mecánico ve solo las propias.
        if (
            !$request->user()->can('mechanic-commissions.view-all') &&
            (int) $mechanic->user_id !== (int) $request->user()->id
        ) {
            return $this->errorResponse(
                'Solo puede consultar sus propias comisiones.',
                403,
                'FORBIDDEN'
            );
        }

        $from = $request->filled('date_from')
            ? Carbon::parse($request->date_from)
            : now()->startOfMonth();

        $to = $request->filled('date_to')
            ? Carbon::parse($request->date_to)
            : now()->endOfMonth();

        $result = $this->commissionService->mechanicCommissions($mechanic, $from, $to);

        $mechanic->loadMissing('user');

        return $this->successResponse(
            'Comisiones del mecánico obtenidas correctamente.',
            [
                'mechanic' => [
                    'id' => $mechanic->id,
                    'employee_code' => $mechanic->employee_code,
                    'full_name' => $mechanic->fullName(),
                ],
                'period' => [
                    'date_from' => $from->toDateString(),
                    'date_to' => $to->toDateString(),
                ],
                'total_commission' => $result['total'],
                'lines' => $result['lines']->map(function ($line) {
                    $item = $line->workOrderItem;
                    $itemTotal = (string) ($item->budgetItem?->total ?? '0');

                    return [
                        'work_order_id' => $item->workOrder->id,
                        'work_order_code' => $item->workOrder->code,
                        'completed_at' => $item->workOrder->completed_at,
                        'work_order_item_id' => $item->id,
                        'description' => $item->description,
                        'item_total' => $itemTotal,
                        'share_percentage' => $line->share_percentage,
                        'commission_percentage' => $line->commission_percentage,
                        'commission_amount' => $line->commissionAmount($itemTotal),
                    ];
                })->values(),
            ],
            200
        );
    }
}
