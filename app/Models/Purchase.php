<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Enums\InvoiceCondition;
use App\Enums\InvoicePaymentStatus;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Exceptions\PurchaseException;
use Carbon\CarbonInterface;
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
    use Auditable, SoftDeletes;

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

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class)->orderBy('id');
    }

    /**
     * Total pagado (pagos no anulados). Usa paid_total si vino de withSum
     * o la relación si está cargada.
     */
    public function paidTotal(): string
    {
        if (array_key_exists('paid_total', $this->attributes)) {
            return bcadd((string) ($this->attributes['paid_total'] ?? '0'), '0', 2);
        }

        $payments = $this->relationLoaded('payments')
            ? $this->payments->where('status', PaymentStatus::REGISTERED)
            : $this->payments()->registered()->get();

        return $payments->reduce(fn (string $sum, SupplierPayment $p) => bcadd($sum, $p->amount, 2), '0.00');
    }

    public function balance(): string
    {
        return bcsub((string) $this->total, $this->paidTotal(), 2);
    }

    /**
     * Estado de pago: solo aplica a compras recibidas.
     */
    public function paymentStatus(): ?InvoicePaymentStatus
    {
        if ($this->status !== PurchaseStatus::RECEIVED) {
            return null;
        }

        return match (true) {
            bccomp($this->balance(), '0', 2) <= 0 => InvoicePaymentStatus::PAID,
            bccomp($this->paidTotal(), '0', 2) > 0 => InvoicePaymentStatus::PARTIAL,
            default => InvoicePaymentStatus::UNPAID,
        };
    }

    /**
     * Fecha en que vence la deuda: el vencimiento a crédito o, al contado,
     * la fecha de la factura del proveedor.
     */
    public function payableDueDate(): ?CarbonInterface
    {
        return $this->due_date ?? $this->supplier_document_date;
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
