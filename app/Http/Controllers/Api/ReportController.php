<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Dashboard y reportes (solo lectura). Un permiso por reporte; el dashboard
 * muestra solo los bloques de los reportes que el usuario puede ver.
 */
class ReportController extends Controller implements HasMiddleware
{
    use ApiResponse;

    /**
     * Bloque del dashboard => permiso que lo habilita.
     */
    private const DASHBOARD_BLOCKS = [
        'sales' => 'reports.sales',
        'budgets' => 'reports.budgets',
        'profitability' => 'reports.profitability',
        'productivity' => 'reports.productivity',
        'inventory' => 'reports.inventory',
        'maintenance' => 'maintenance-alerts.index',
    ];

    public function __construct(
        protected ReportService $reportService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:dashboard.view', only: ['dashboard']),
            new Middleware('permission:reports.sales', only: ['sales']),
            new Middleware('permission:reports.budgets', only: ['budgets']),
            new Middleware('permission:reports.profitability', only: ['profitability']),
            new Middleware('permission:reports.productivity', only: ['productivity']),
            new Middleware('permission:reports.inventory', only: ['inventory']),
            new Middleware('permission:reports.clients', only: ['clients']),
        ];
    }

    public function dashboard(Request $request)
    {
        $allowed = collect(self::DASHBOARD_BLOCKS)
            ->filter(fn (string $permission) => $request->user()->can($permission))
            ->keys()
            ->all();

        return $this->successResponse(
            'Dashboard obtenido correctamente.',
            $this->reportService->dashboard($allowed),
            200
        );
    }

    public function sales(ReportRequest $request)
    {
        return $this->report('Reporte de ventas obtenido correctamente.', $this->reportService->sales(
            $request->from(),
            $request->to(),
            $request->group_by ?? 'day'
        ));
    }

    public function budgets(ReportRequest $request)
    {
        return $this->report('Reporte de presupuestos obtenido correctamente.', $this->reportService->budgets($request->from(), $request->to()));
    }

    public function profitability(ReportRequest $request)
    {
        return $this->report('Reporte de rentabilidad obtenido correctamente.', $this->reportService->profitability($request->from(), $request->to()));
    }

    public function productivity(ReportRequest $request)
    {
        return $this->report('Reporte de productividad obtenido correctamente.', $this->reportService->productivity($request->from(), $request->to()));
    }

    public function inventory(ReportRequest $request)
    {
        return $this->report('Reporte de inventario obtenido correctamente.', $this->reportService->inventory($request->from(), $request->to()));
    }

    public function clients(ReportRequest $request)
    {
        return $this->report('Reporte de clientes obtenido correctamente.', $this->reportService->clients($request->from(), $request->to()));
    }

    private function report(string $message, array $data)
    {
        return $this->successResponse($message, $data, 200);
    }
}
