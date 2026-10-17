<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pago contra una factura emitida. No se edita ni se elimina: se anula.
 */
class Payment extends Model
{
    /**
     * Campos que pueden cambiar una vez registrado (solo la anulación).
     */
    private const CANCELLATION_FIELDS = ['status', 'cancelled_at', 'cancelled_by', 'cancellation_reason', 'updated_at'];

    protected $fillable = [
        'invoice_id',
        'receipt_sequence',
        'receipt_number',
        'payment_date',
        'amount',
        'method',
        'reference',
        'bank',
        'notes',
        'status',
        'created_by',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Payment $payment) {
            $original = PaymentStatus::from($payment->getRawOriginal('status'));
            $changed = array_diff(array_keys($payment->getDirty()), self::CANCELLATION_FIELDS);

            if ($original === PaymentStatus::CANCELLED || $changed !== []) {
                throw new PaymentException(
                    'El pago registrado no puede modificarse; solo puede anularse.',
                    409,
                    'PAYMENT_NOT_EDITABLE'
                );
            }
        });

        static::deleting(function () {
            throw new PaymentException(
                'Los pagos no se eliminan; deben anularse.',
                409,
                'PAYMENT_NOT_EDITABLE'
            );
        });
    }

    public function scopeRegistered(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::REGISTERED);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
