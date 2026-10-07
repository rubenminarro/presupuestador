<?php

namespace App\Services;

use App\Exceptions\SupplierException;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    public function delete(Supplier $supplier): void
    {
        DB::transaction(function () use ($supplier) {

            if ($supplier->parts()->exists()) {
                throw new SupplierException(
                    'No se puede eliminar el proveedor porque tiene repuestos vinculados. Desvincúlelos o márquelo como inactivo.',
                    409,
                    'SUPPLIER_HAS_PARTS'
                );
            }

            $supplier->delete();
        });
    }
}
