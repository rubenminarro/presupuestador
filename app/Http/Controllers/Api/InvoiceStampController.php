<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceStampRequest;
use App\Http\Requests\UpdateInvoiceStampRequest;
use App\Http\Resources\InvoiceStampResource;
use App\Models\InvoiceStamp;
use App\Services\InvoiceStampService;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class InvoiceStampController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public function __construct(
        protected InvoiceStampService $stampService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:invoice-stamps.index', only: ['index']),
            new Middleware('permission:invoice-stamps.store', only: ['store']),
            new Middleware('permission:invoice-stamps.update', only: ['update']),
        ];
    }

    public function index()
    {
        $stamps = InvoiceStamp::query()
            ->orderByDesc('is_active')
            ->orderByDesc('valid_until')
            ->get();

        return $this->successResponse(
            'Timbrados obtenidos correctamente.',
            InvoiceStampResource::collection($stamps),
            200
        );
    }

    public function store(StoreInvoiceStampRequest $request)
    {
        $stamp = $this->stampService->create($request->validated());

        return $this->successResponse(
            'Timbrado registrado correctamente.',
            new InvoiceStampResource($stamp),
            201
        );
    }

    public function update(UpdateInvoiceStampRequest $request, InvoiceStamp $invoiceStamp)
    {
        $stamp = $this->stampService->update($invoiceStamp, $request->validated());

        return $this->successResponse(
            'Timbrado actualizado correctamente.',
            new InvoiceStampResource($stamp),
            200
        );
    }
}
