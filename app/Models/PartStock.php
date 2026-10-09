<?php

namespace App\Models;

use App\Support\Decimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartStock extends Model
{
    protected $fillable = [
        'part_id',
        'warehouse_id',
        'quantity_on_hand',
        'quantity_reserved',
        'average_cost',
        'last_movement_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:3',
            'quantity_reserved' => 'decimal:3',
            'average_cost' => 'decimal:4',
            'last_movement_at' => 'datetime',
        ];
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class)->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function availableQuantity(): string
    {
        return bcsub(
            $this->quantity_on_hand ?? '0',
            $this->quantity_reserved ?? '0',
            3
        );
    }

    public function isBelowMinimum(?string $minimumStock): bool
    {
        if ($minimumStock === null || bccomp($minimumStock, '0', 3) <= 0) {
            return false;
        }

        return bccomp($this->availableQuantity(), $minimumStock, 3) < 0;
    }

    public function stockValue(): string
    {
        return Decimal::round(
            bcmul($this->quantity_on_hand ?? '0', $this->average_cost ?? '0', 8),
            2
        );
    }
}
