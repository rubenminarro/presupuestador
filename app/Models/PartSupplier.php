<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PartSupplier extends Pivot
{
    protected $table = 'part_supplier';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'last_cost' => 'decimal:2',
            'is_preferred' => 'boolean',
        ];
    }
}
