<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexMechanicCommissionRequest;
use App\Http\Requests\SettleMechanicCommissionRequest;
use App\Models\Mechanic;
use App\Services\WorkOrderCommissionService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Comisiones congeladas de un mecánico en un período (por defecto, el mes en curso)
 * y su liquidación.
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
            new Middleware('permission:mechanic-commissions.settle', only: ['settle']),
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

        $result = $this->commissionService->mechanicCommissions($mechanic, $from, $to, $request->status);

        return $this->successResponse(
            'Comisiones del mecánico obtenidas correctamente.',
            [
                'mechanic' => $this->mechanicData($mechanic),
                'period' => [
                    'date_from' => $from->toDateString(),
                    'date_to' => $to->toDateString(),
                ],
                'total_commission' => $result['total'],
                'total_pending' => $result['total_pending'],
                'total_settled' => $result['total_settled'],
                'lines' => $result['lines']->map(fn ($line) => [
                    'id' => $line->id,
                    'work_order_id' => $line->work_order_id,
                    'work_order_code' => $line->workOrder->code,
                    'completed_at' => $line->earned_at,
                    'work_order_item_id' => $line->work_order_item_id,
                    'description' => $line->workOrderItem->description,
                    'item_total' => $line->base_amount,
                    'share_percentage' => $line->share_percentage,
                    'commission_percentage' => $line->commission_percentage,
                    'commission_amount' => $line->amount,
                    'settled_at' => $line->settled_at,
                ])->values(),
            ],
            200
        );
    }

    public function settle(SettleMechanicCommissionRequest $request, Mechanic $mechanic)
    {
        $from = Carbon::parse($request->date_from);
        $to = Carbon::parse($request->date_to);

        $result = $this->commissionService->settle($mechanic, $from, $to, $request->user()->id);

        return $this->successResponse(
            'Comisiones liquidadas correctamente.',
            [
                'mechanic' => $this->mechanicData($mechanic),
                'period' => [
                    'date_from' => $from->toDateString(),
                    'date_to' => $to->toDateString(),
                ],
                'settled_count' => $result['count'],
                'settled_total' => $result['total'],
                'settled_at' => $result['settled_at'],
            ],
            200
        );
    }

    private function mechanicData(Mechanic $mechanic): array
    {
        $mechanic->loadMissing('user');

        return [
            'id' => $mechanic->id,
            'employee_code' => $mechanic->employee_code,
            'full_name' => $mechanic->fullName(),
        ];
    }
}
