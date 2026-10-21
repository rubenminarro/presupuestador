<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\SupplierPaymentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pago a un proveedor contra una compra recibida. No se edita ni se elimina: se anula.
 */
class SupplierPayment extends Model
{
    use Auditable;

    /**
     * Campos que pueden cambiar una vez registrado (solo la anulación).
     */
    private const CANCELLATION_FIELDS = ['status', 'cancelled_at', 'cancelled_by', 'cancellation_reason', 'updated_at'];

    protected $fillable = [
        'code',
        'purchase_id',
        'supplier_id',
        'payment_date',
        'amount',
        'method',
        'reference',
        'bank',
        'supplier_receipt_number',
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
        static::updating(function (SupplierPayment $payment) {
            $original = PaymentStatus::from($payment->getRawOriginal('status'));
            $changed = array_diff(array_keys($payment->getDirty()), self::CANCELLATION_FIELDS);

            if ($original === PaymentStatus::CANCELLED || $changed !== []) {
                throw new SupplierPaymentException(
                    'El pago al proveedor no puede modificarse; solo puede anularse.',
                    409,
                    'SUPPLIER_PAYMENT_NOT_EDITABLE'
                );
            }
        });

        static::deleting(function () {
            throw new SupplierPaymentException(
                'Los pagos a proveedores no se eliminan; deben anularse.',
                409,
                'SUPPLIER_PAYMENT_NOT_EDITABLE'
            );
        });
    }

    public function scopeRegistered(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::REGISTERED);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class)->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
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
