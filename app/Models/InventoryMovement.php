<?php

namespace App\Models;

use App\Enums\InventoryMovementType;
use App\Exceptions\InventoryException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'part_id',
        'warehouse_id',
        'type',
        'quantity',
        'unit_cost',
        'total_cost',
        'balance_after',
        'average_cost_after',
        'supplier_id',
        'document_number',
        'reference_type',
        'reference_id',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => InventoryMovementType::class,
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:2',
            'balance_after' => 'decimal:3',
            'average_cost_after' => 'decimal:4',
        ];
    }

    /**
     * Los movimientos son un registro histórico: se corrigen con
     * movimientos inversos, nunca editándolos ni eliminándolos.
     */
    protected static function booted(): void
    {
        $immutable = function () {
            throw new InventoryException(
                'Los movimientos de inventario no pueden modificarse ni eliminarse.',
                409,
                'INVENTORY_MOVEMENT_IMMUTABLE'
            );
        };

        static::updating($immutable);
        static::deleting($immutable);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class)->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
