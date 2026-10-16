<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reception_id',
        'budget_id',
        'mechanic_id',
        'created_by',
        'code',
        'status',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkOrderStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Mechanic::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(WorkOrderPart::class);
    }

    public function closing(): HasOne
    {
        return $this->hasOne(WorkOrderClosing::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(WorkOrderCommission::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Factura vigente (borrador o emitida); las anuladas no cuentan.
     */
    public function currentInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->whereIn('status', InvoiceStatus::currentStatuses())
        );
    }
}
