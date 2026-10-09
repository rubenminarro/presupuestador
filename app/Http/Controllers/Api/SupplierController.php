<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\ShowSupplierResource;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SupplierController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:suppliers.index', only: ['index']),
            new Middleware('permission:suppliers.store', only: ['store']),
            new Middleware('permission:suppliers.show', only: ['show']),
            new Middleware('permission:suppliers.update', only: ['update']),
            new Middleware('permission:suppliers.destroy', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $suppliers = Supplier::query()
            ->withCount('parts')
            ->when($request->filled('search'), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('tax_id', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->orderBy('name')
            ->paginate($request->per_page ?? 10);

        return $this->successResponse(
            'Proveedores obtenidos correctamente.',
            SupplierResource::collection($suppliers->items()),
            200,
            [
                'pagination' => [
                    'total'       => $suppliers->total(),
                    'perPage'     => $suppliers->perPage(),
                    'currentPage' => $suppliers->currentPage(),
                    'lastPage'    => $suppliers->lastPage(),
                ],
            ]
        );
    }

    public function store(StoreSupplierRequest $request)
    {
        $supplier = Supplier::create($request->validated());

        return $this->successResponse(
            'Proveedor creado correctamente.',
            new ShowSupplierResource($supplier->refresh()),
            201
        );
    }

    public function show(Supplier $supplier)
    {
        $supplier->load('parts');

        return $this->successResponse(
            'Proveedor obtenido correctamente.',
            new ShowSupplierResource($supplier),
            200
        );
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $supplier->update($request->validated());

        return $this->successResponse(
            'Proveedor actualizado correctamente.',
            new ShowSupplierResource($supplier->load('parts')),
            200
        );
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->parts()->exists()) {
            return $this->errorResponse(
                'No se puede eliminar el proveedor porque tiene repuestos vinculados. Desvincúlelos o márquelo como inactivo.',
                409,
                'SUPPLIER_HAS_PARTS'
            );
        }

        $supplier->delete();

        return $this->successResponse(
            'Proveedor eliminado correctamente.',
            null,
            200
        );
    }
}