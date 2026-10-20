<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelPaymentRequest;
use App\Http\Requests\IndexPaymentRequest;
use App\Http\Requests\PaymentSummaryRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\ShowPaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\DocumentPdfService;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use App\Traits\FileResponse;
use Carbon\Carbon;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PaymentController extends Controller implements HasMiddleware
{
    use ApiResponse, FileResponse;

    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:payments.index', only: ['index']),
            new Middleware('permission:payments.show', only: ['show', 'receipt']),
            new Middleware('permission:payments.store', only: ['store']),
            new Middleware('permission:payments.cancel', only: ['cancel']),
            new Middleware('permission:payments.summary', only: ['summary']),
        ];
    }

    public function index(IndexPaymentRequest $request)
    {
        $search = trim($request->input('search', ''));

        $payments = Payment::query()
            ->with('invoice')
            ->when($request->filled('invoice_id'), fn ($q) => $q->where('invoice_id', $request->invoice_id))
            ->when($request->filled('client_id'), fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('client_id', $request->client_id)))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->method))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('receipt_number', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('invoice', fn ($i) => $i->where('number', 'like', "%{$search}%")
                            ->orWhere('customer_name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('payment_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('payment_date', '<=', $request->date_to))
            ->orderByDesc('id')
            ->paginate($request->per_page ?? 10);

        return $this->successResponse(
            'Pagos obtenidos correctamente.',
            PaymentResource::collection($payments->items()),
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

    public function show(Payment $payment)
    {
        return $this->successResponse(
            'Pago obtenido correctamente.',
            new ShowPaymentResource($payment->load('invoice')),
            200
        );
    }

    /**
     * Recibo imprimible (solo pagos de facturas a crédito).
     */
    public function receipt(Payment $payment, DocumentPdfService $documentPdfService)
    {
        return $this->fileResponse($documentPdfService->receipt($payment), 'application/pdf');
    }

    public function store(StorePaymentRequest $request, Invoice $invoice)
    {
        $payment = $this->paymentService->register($invoice, $request->validated(), $request->user());

        return $this->successResponse(
            'Pago registrado correctamente.',
            new ShowPaymentResource($payment),
            201
        );
    }

    public function cancel(CancelPaymentRequest $request, Payment $payment)
    {
        $payment = $this->paymentService->cancel($payment, $request->validated('reason'), $request->user());

        return $this->successResponse(
            'Pago anulado correctamente.',
            new ShowPaymentResource($payment),
            200
        );
    }

    /**
     * Resumen de caja por medio de pago (por defecto, el día de hoy).
     */
    public function summary(PaymentSummaryRequest $request)
    {
        $from = $request->filled('date_from') ? Carbon::parse($request->date_from) : now();
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to) : $from->copy();

        return $this->successResponse(
            'Resumen de caja obtenido correctamente.',
            $this->paymentService->summary($from, $to),
            200
        );
    }
}
