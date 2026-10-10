<?php

namespace App\Models;

use App\Enums\WorkOrderPartStatus;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class WorkOrderPart extends Model
{
    protected $fillable = [
        'work_order_id',
        'work_order_item_id',
        'part_id',
        'status',
        'quantity',
        'quantity_consumed',
        'quantity_returned',
        'unit_cost',
        'unit_price',
        'notes',
        'added_by',
        'consumed_by',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkOrderPartStatus::class,
            'quantity' => 'decimal:3',
            'quantity_consumed' => 'decimal:3',
            'quantity_returned' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'consumed_at' => 'datetime',
        ];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', WorkOrderPartStatus::openStatuses());
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function workOrderItem(): BelongsTo
    {
        return $this->belongsTo(WorkOrderItem::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class)->withTrashed();
    }

    public function adder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumed_by');
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }

    /**
     * Cantidad realmente utilizada: consumida menos devuelta.
     */
    public function netQuantity(): string
    {
        return bcsub($this->quantity_consumed ?? '0', $this->quantity_returned ?? '0', 3);
    }

    public function totalCost(): ?string
    {
        if ($this->unit_cost === null) {
            return null;
        }

        return Decimal::round(bcmul($this->netQuantity(), $this->unit_cost, 8), 2);
    }

    public function totalPrice(): string
    {
        return Decimal::round(bcmul($this->netQuantity(), $this->unit_price ?? '0', 8), 2);
    }
}
