<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountsPayableRequest;
use App\Http\Requests\CancelPaymentRequest;
use App\Http\Requests\IndexSupplierPaymentRequest;
use App\Http\Requests\PaymentSummaryRequest;
use App\Http\Requests\StoreSupplierPaymentRequest;
use App\Http\Resources\ShowSupplierPaymentResource;
use App\Http\Resources\SupplierPaymentResource;
use App\Models\Purchase;
use App\Models\SupplierPayment;
use App\Services\SupplierPaymentService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SupplierPaymentController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public function __construct(
        protected SupplierPaymentService $supplierPaymentService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:supplier-payments.index', only: ['index', 'purchasePayments']),
            new Middleware('permission:supplier-payments.show', only: ['show']),
            new Middleware('permission:supplier-payments.store', only: ['store']),
            new Middleware('permission:supplier-payments.cancel', only: ['cancel']),
            new Middleware('permission:supplier-payments.summary', only: ['summary']),
            new Middleware('permission:accounts-payable.view', only: ['accountsPayable']),
        ];
    }

    public function index(IndexSupplierPaymentRequest $request)
    {
        $search = trim($request->input('search', ''));

        $payments = SupplierPayment::query()
            ->with(['purchase', 'supplier'])
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('purchase_id'), fn ($q) => $q->where('purchase_id', $request->purchase_id))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->method))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('supplier_receipt_number', 'like', "%{$search}%")
                        ->orWhereHas('purchase', fn ($p) => $p->where('code', 'like', "%{$search}%")
                            ->orWhere('supplier_document_number', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('payment_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('payment_date', '<=', $request->date_to))
            ->orderByDesc('id')
            ->paginate($request->per_page ?? 10);

        return $this->successResponse(
            'Pagos a proveedores obtenidos correctamente.',
            SupplierPaymentResource::collection($payments->items()),
            200,
            [
                'pagination' => [
                    'total'       => $payments->total(),
                    'perPage'     => $payments->perPage(),
                    'currentPage' => $payments->currentPage(),
                    'lastPage'    => $payments->lastPage(),
                ]
            ]
        );
    }

    public function purchasePayments(Purchase $purchase)
    {
        return $this->successResponse(
            'Pagos de la compra obtenidos correctamente.',
            SupplierPaymentResource::collection($purchase->payments()->get()),
            200
        );
    }

    public function show(SupplierPayment $supplierPayment)
    {
        return $this->successResponse(
            'Pago al proveedor obtenido correctamente.',
            new ShowSupplierPaymentResource($supplierPayment->load(['purchase', 'supplier'])),
            200
        );
    }

    public function store(StoreSupplierPaymentRequest $request, Purchase $purchase)
    {
        $payment = $this->supplierPaymentService->register($purchase, $request->validated(), $request->user());

        return $this->successResponse(
            'Pago al proveedor registrado correctamente.',
            new ShowSupplierPaymentResource($payment),
            201
        );
    }

    public function cancel(CancelPaymentRequest $request, SupplierPayment $supplierPayment)
    {
        $payment = $this->supplierPaymentService->cancel($supplierPayment, $request->validated('reason'), $request->user());

        return $this->successResponse(
            'Pago al proveedor anulado correctamente.',
            new ShowSupplierPaymentResource($payment),
            200
        );
    }

    /**
     * Egresos a proveedores por medio de pago (por defecto, el día de hoy).
     */
    public function summary(PaymentSummaryRequest $request)
    {
        $from = $request->filled('date_from') ? Carbon::parse($request->date_from) : now();
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to) : $from->copy();

        return $this->successResponse(
            'Resumen de pagos a proveedores obtenido correctamente.',
            $this->supplierPaymentService->summary($from, $to),
            200
        );
    }

    public function accountsPayable(AccountsPayableRequest $request)
    {
        return $this->successResponse(
            'Cuentas por pagar obtenidas correctamente.',
            $this->supplierPaymentService->accountsPayable($request->filled('supplier_id') ? (int) $request->supplier_id : null),
            200
        );
    }
}
