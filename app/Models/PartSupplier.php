<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PartSupplier extends Pivot
{
    use Auditable;

    protected $table = 'part_supplier';

    public $incrementing = true;

    /**
     * Las modificaciones del pivote pueden no traer su id: se identifica
     * por repuesto y proveedor.
     */
    public function auditContext(): array
    {
        return [
            'part_id' => $this->part_id ?? $this->getRawOriginal('part_id'),
            'supplier_id' => $this->supplier_id ?? $this->getRawOriginal('supplier_id'),
        ];
    }

    protected function casts(): array
    {
        return [
            'last_cost' => 'decimal:2',
            'is_preferred' => 'boolean',
        ];
    }
}
