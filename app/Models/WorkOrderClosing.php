<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cierre económico congelado de una OT completada (importes sin IVA).
 */
class WorkOrderClosing extends Model
{
    protected $fillable = [
        'work_order_id',
        'closed_by',
        'closed_at',
        'budgeted_labor',
        'budgeted_parts',
        'budgeted_total',
        'sale_labor',
        'sale_parts',
        'sale_total',
        'cost_parts',
        'cost_labor',
        'commission_total',
        'cost_total',
        'gross_margin',
        'margin_percentage',
        'is_cost_complete',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
            'budgeted_labor' => 'decimal:2',
            'budgeted_parts' => 'decimal:2',
            'budgeted_total' => 'decimal:2',
            'sale_labor' => 'decimal:2',
            'sale_parts' => 'decimal:2',
            'sale_total' => 'decimal:2',
            'cost_parts' => 'decimal:2',
            'cost_labor' => 'decimal:2',
            'commission_total' => 'decimal:2',
            'cost_total' => 'decimal:2',
            'gross_margin' => 'decimal:2',
            'margin_percentage' => 'decimal:2',
            'is_cost_complete' => 'boolean',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
