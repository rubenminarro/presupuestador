<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comisión congelada al completar la OT: una por mecánico y trabajo.
 * settled_at null = pendiente de liquidar.
 */
class WorkOrderCommission extends Model
{
    protected $fillable = [
        'work_order_id',
        'work_order_item_id',
        'mechanic_id',
        'base_amount',
        'share_percentage',
        'commission_percentage',
        'amount',
        'earned_at',
        'settled_at',
        'settled_by',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'share_percentage' => 'decimal:2',
            'commission_percentage' => 'decimal:2',
            'amount' => 'decimal:2',
            'earned_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('settled_at');
    }

    public function scopeSettled(Builder $query): Builder
    {
        return $query->whereNotNull('settled_at');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class)->withTrashed();
    }

    public function workOrderItem(): BelongsTo
    {
        return $this->belongsTo(WorkOrderItem::class)->withTrashed();
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Mechanic::class)->withTrashed();
    }

    public function settler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }
}
