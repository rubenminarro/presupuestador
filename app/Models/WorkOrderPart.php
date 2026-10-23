<?php

namespace App\Models;

use App\Enums\ClientDecision;
use App\Enums\ClientDecisionChannel;
use App\Enums\WorkOrderPartStatus;
use App\Support\Decimal;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class WorkOrderPart extends Model
{
    use Auditable;

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
        'client_decision',
        'decided_at',
        'decided_by',
        'decision_channel',
        'decision_contact',
        'decision_notes',
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
            'client_decision' => ClientDecision::class,
            'decided_at' => 'datetime',
            'decision_channel' => ClientDecisionChannel::class,
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

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
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
     * Repuesto agregado en la OT, fuera del presupuesto aprobado.
     */
    public function isExtra(): bool
    {
        return $this->work_order_item_id === null;
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
