<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartStockResource;
use App\Models\Part;
use App\Models\PartStock;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class InventoryStockController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory.index', only: ['index']),
        ];
    }

    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $query = PartStock::query()
            ->with(['part.category'])
            ->whereHas('part', function ($q) use ($request, $search) {

                // La relación incluye eliminados (para el historial); el listado no.
                $q->withoutTrashed();

                $q->when($request->filled('search'), function ($q) use ($search) {
                    $q->where(function ($q) use ($search) {
                        $q->where('code', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('part_number', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%");
                    });
                });

                $q->when($request->filled('category_id'), function ($q) use ($request) {
                    $q->where('part_category_id', $request->category_id);
                });

                $q->when($request->filled('status'), function ($q) use ($request) {
                    $q->where('status', $request->status);
                });

                $q->when($request->boolean('below_minimum'), function ($q) {
                    $q->where('minimum_stock', '>', 0)
                        ->whereRaw('(part_stocks.quantity_on_hand - part_stocks.quantity_reserved) < parts.minimum_stock');
                });
            });

        $query->when($request->boolean('with_stock'), function ($q) {
            $q->where('quantity_on_hand', '>', 0);
        });

        $stocks = $query
            ->orderBy(
                Part::withTrashed()->select('name')->whereColumn('parts.id', 'part_stocks.part_id')
            )
            ->paginate($request->per_page ?? 10);

        return $this->successResponse(
            'Stock obtenido correctamente.',
            PartStockResource::collection($stocks->items()),
            200,
            [
                'pagination' => [
                    'total'       => $stocks->total(),
                    'perPage'     => $stocks->perPage(),
                    'currentPage' => $stocks->currentPage(),
                    'lastPage'    => $stocks->lastPage(),
                ]
            ]
        );
    }
}
