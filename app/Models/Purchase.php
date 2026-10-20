<?php

namespace App\Models;

use App\Enums\InvoiceCondition;
use App\Enums\PurchaseStatus;
use App\Exceptions\PurchaseException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Compra a un proveedor. Importes con IVA incluido (como la factura del
 * proveedor) salvo net_total, que es el costo sin IVA que entra al inventario.
 */
class Purchase extends Model
{
    use SoftDeletes;

    /**
     * Campos que pueden cambiar una vez recibida (solo la anulación).
     */
    private const CANCELLATION_FIELDS = ['status', 'cancelled_at', 'cancelled_by', 'cancellation_reason', 'updated_at'];

    protected $fillable = [
        'code',
        'supplier_id',
        'status',
        'supplier_document_number',
        'supplier_document_date',
        'supplier_stamp_number',
        'condition',
        'due_date',
        'total_exempt',
        'total_5',
        'total_10',
        'iva_5',
        'iva_10',
        'total',
        'net_total',
        'notes',
        'created_by',
        'received_at',
        'received_by',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseStatus::class,
            'condition' => InvoiceCondition::class,
            'supplier_document_date' => 'date',
            'due_date' => 'date',
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'total_exempt' => 'decimal:2',
            'total_5' => 'decimal:2',
            'total_10' => 'decimal:2',
            'iva_5' => 'decimal:2',
            'iva_10' => 'decimal:2',
            'total' => 'decimal:2',
            'net_total' => 'decimal:2',
        ];
    }

    /**
     * Una compra recibida movió stock: solo puede anularse.
     */
    protected static function booted(): void
    {
        static::updating(function (Purchase $purchase) {
            $original = PurchaseStatus::from($purchase->getRawOriginal('status'));

            if ($original === PurchaseStatus::DRAFT) {
                return;
            }

            $changed = array_diff(array_keys($purchase->getDirty()), self::CANCELLATION_FIELDS);

            if ($original === PurchaseStatus::CANCELLED || $changed !== []) {
                throw new PurchaseException(
                    'La compra recibida no puede modificarse; solo puede anularse.',
                    409,
                    'PURCHASE_NOT_EDITABLE'
                );
            }
        });

        static::deleting(function (Purchase $purchase) {
            if ($purchase->status !== PurchaseStatus::DRAFT) {
                throw new PurchaseException(
                    'Solo puede eliminarse una compra en borrador.',
                    409,
                    'PURCHASE_NOT_EDITABLE'
                );
            }
        });
    }

    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', PurchaseStatus::CANCELLED->value);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class)->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
