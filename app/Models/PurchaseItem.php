<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use App\Enums\TaxRate;
use App\Exceptions\PurchaseException;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de compra. unit_cost / total: con IVA incluido, como en la factura del
 * proveedor. net_unit_cost: sin IVA, es el costo que entra al inventario.
 */
class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'part_id',
        'quantity',
        'unit_cost',
        'tax_rate',
        'total',
        'net_unit_cost',
        'inventory_movement_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'tax_rate' => TaxRate::class,
            'total' => 'decimal:2',
            'net_unit_cost' => 'decimal:4',
        ];
    }

    /**
     * Las líneas solo cambian mientras la compra está en borrador
     * (al recibir se completa inventory_movement_id antes del cambio de estado).
     */
    protected static function booted(): void
    {
        $guard = function (PurchaseItem $item) {
            $status = Purchase::withTrashed()->whereKey($item->purchase_id)->value('status');

            if ($status !== null && $status !== PurchaseStatus::DRAFT) {
                throw new PurchaseException(
                    'Las líneas de una compra recibida no pueden modificarse.',
                    409,
                    'PURCHASE_NOT_EDITABLE'
                );
            }
        };

        static::updating($guard);
        static::deleting($guard);
    }

    /**
     * Total con IVA (moneda: config invoice.decimals) y costo unitario sin IVA
     * (4 decimales, la escala del costo de inventario).
     */
    public function recalculate(): void
    {
        $decimals = (int) config('invoice.decimals', 0);
        $rate = (string) $this->tax_rate->value;

        $this->total = Decimal::round(bcmul($this->quantity, $this->unit_cost, 8), $decimals);

        $this->net_unit_cost = Decimal::round(
            bcdiv(bcmul($this->unit_cost, '100', 8), bcadd('100', $rate, 0), 8),
            4
        );
    }

    /**
     * Total sin IVA de la línea: lo que vale en el inventario.
     */
    public function netTotal(): string
    {
        return Decimal::round(bcmul($this->quantity, $this->net_unit_cost, 8), 2);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class)->withTrashed();
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }
}
