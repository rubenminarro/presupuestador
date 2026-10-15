<?php

namespace App\Models;

use App\Support\Decimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mecánico asignado a un trabajo (labor/service) de la OT, con su
 * participación en el trabajo y el % de comisión vigente al asignarlo.
 */
class WorkOrderItemMechanic extends Model
{
    protected $fillable = [
        'work_order_item_id',
        'mechanic_id',
        'share_percentage',
        'commission_percentage',
        'hours',
        'hour_cost',
    ];

    protected function casts(): array
    {
        return [
            'share_percentage' => 'decimal:2',
            'commission_percentage' => 'decimal:2',
            'hours' => 'decimal:2',
            'hour_cost' => 'decimal:2',
        ];
    }

    /**
     * Costo de mano de obra = horas × costo por hora copiado.
     * Sin horas cargadas el costo es desconocido (null), nunca cero.
     */
    public function laborCost(): ?string
    {
        if ($this->hours === null || $this->hour_cost === null) {
            return null;
        }

        return Decimal::round(bcmul($this->hours, $this->hour_cost, 8), 2);
    }

    public function workOrderItem(): BelongsTo
    {
        return $this->belongsTo(WorkOrderItem::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Mechanic::class)->withTrashed();
    }

    /**
     * total del ítem (sin IVA) × % del trabajo × % de comisión.
     */
    public function commissionAmount(string $itemTotal): string
    {
        $amount = bcdiv(
            bcmul(bcmul($itemTotal, $this->share_percentage, 8), $this->commission_percentage, 8),
            '10000',
            8
        );

        return Decimal::round($amount, 2);
    }
}
