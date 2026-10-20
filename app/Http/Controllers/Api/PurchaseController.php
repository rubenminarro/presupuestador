<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelInvoiceRequest;
use App\Http\Requests\IndexPurchaseRequest;
use App\Http\Requests\StorePurchaseItemRequest;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseItemRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Http\Resources\ShowPurchaseResource;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\PurchaseService;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PurchaseController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public function __construct(
        protected PurchaseService $purchaseService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:purchases.index', only: ['index']),
            new Middleware('permission:purchases.show', only: ['show']),
            new Middleware('permission:purchases.store', only: ['store']),
            new Middleware('permission:purchases.update', only: ['update', 'storeItem', 'updateItem', 'destroyItem']),
            new Middleware('permission:purchases.destroy', only: ['destroy']),
            new Middleware('permission:purchases.receive', only: ['receive']),
            new Middleware('permission:purchases.cancel', only: ['cancel']),
        ];
    }

    public function index(IndexPurchaseRequest $request)
    {
        $search = trim($request->input('search', ''));

        $purchases = Purchase::query()
            ->with('supplier')
            ->withSum(['payments as paid_total' => fn ($q) => $q->registered()], 'amount')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('supplier_document_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('supplier_document_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('supplier_document_date', '<=', $request->date_to))
            ->orderByDesc('id')
            ->paginate($request->per_page ?? 10);

        return $this->successResponse(
            'Compras obtenidas correctamente.',
            PurchaseResource::collection($purchases->items()),
            200,
            [
                'pagination' => [
                    'total'       => $purchases->total(),
                    'perPage'     => $purchases->perPage(),
                    'currentPage' => $purchases->currentPage(),
                    'lastPage'    => $purchases->lastPage(),
                ]
            ]
        );
    }

    public function show(Purchase $purchase)
    {
        return $this->successResponse(
            'Compra obtenida correctamente.',
            new ShowPurchaseResource($purchase->load(['supplier', 'items.part', 'payments'])),
            200
        );
    }

    public function store(StorePurchaseRequest $request)
    {
        $purchase = $this->purchaseService->create($request->validated(), $request->user());

        return $this->successResponse(
            'Compra creada en borrador correctamente.',
            new ShowPurchaseResource($purchase),
            201
        );
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase)
    {
        $purchase = $this->purchaseService->update($purchase, $request->validated());

        return $this->successResponse(
            'Compra actualizada correctamente.',
            new ShowPurchaseResource($purchase),
            200
        );
    }

    public function destroy(Purchase $purchase)
    {
        $this->purchaseService->delete($purchase);

        return $this->successResponse(
            'Borrador de compra eliminado correctamente.',
            null,
            200
        );
    }

    public function storeItem(StorePurchaseItemRequest $request, Purchase $purchase)
    {
        $purchase = $this->purchaseService->addItem($purchase, $request->validated());

        return $this->successResponse(
            'Línea agregada a la compra correctamente.',
            new ShowPurchaseResource($purchase),
            201
        );
    }

    public function updateItem(UpdatePurchaseItemRequest $request, Purchase $purchase, PurchaseItem $item)
    {
        $purchase = $this->purchaseService->updateItem($purchase, $item, $request->validated());

        return $this->successResponse(
            'Línea de la compra actualizada correctamente.',
            new ShowPurchaseResource($purchase),
            200
        );
    }

    public function destroyItem(Purchase $purchase, PurchaseItem $item)
    {
        $purchase = $this->purchaseService->removeItem($purchase, $item);

        return $this->successResponse(
            'Línea de la compra eliminada correctamente.',
            new ShowPurchaseResource($purchase),
            200
        );
    }

    public function receive(Purchase $purchase)
    {
        $purchase = $this->purchaseService->receive($purchase, request()->user());

        return $this->successResponse(
            'Compra recibida: el stock fue ingresado correctamente.',
            new ShowPurchaseResource($purchase),
            200
        );
    }

    public function cancel(CancelInvoiceRequest $request, Purchase $purchase)
    {
        $purchase = $this->purchaseService->cancel($purchase, $request->validated('reason'), $request->user());

        return $this->successResponse(
            'Compra anulada: el stock fue revertido correctamente.',
            new ShowPurchaseResource($purchase),
            200
        );
    }
}
