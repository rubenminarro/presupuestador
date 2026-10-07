<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachPartSupplierRequest;
use App\Http\Requests\UpdatePartSupplierRequest;
use App\Http\Resources\ShowPartResource;
use App\Models\Part;
use App\Models\Supplier;
use App\Services\PartService;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PartSupplierController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public function __construct(
        protected PartService $partService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:parts.manage-suppliers'),
        ];
    }

    public function store(AttachPartSupplierRequest $request, Part $part)
    {
        $data = $request->validated();

        $supplier = Supplier::findOrFail($data['supplier_id']);

        $part = $this->partService->attachSupplier($part, $supplier, $data);

        return $this->successResponse(
            'Proveedor vinculado al repuesto correctamente.',
            new ShowPartResource($part),
            201
        );
    }

    public function update(UpdatePartSupplierRequest $request, Part $part, Supplier $supplier)
    {
        $part = $this->partService->updateSupplier($part, $supplier, $request->validated());

        return $this->successResponse(
            'Proveedor del repuesto actualizado correctamente.',
            new ShowPartResource($part),
            200
        );
    }

    public function destroy(Part $part, Supplier $supplier)
    {
        $part = $this->partService->detachSupplier($part, $supplier);

        return $this->successResponse(
            'Proveedor desvinculado del repuesto correctamente.',
            new ShowPartResource($part),
            200
        );
    }
}
