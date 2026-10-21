<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\MechanicStatus;

class Mechanic extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_code',
        'specialty',
        'hire_date',
        'hour_cost',
        'commission_percentage',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => MechanicStatus::class,
        'hire_date' => 'date',
        'hour_cost' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function diagnostics(): HasMany
    {
        return $this->hasMany(Diagnostic::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function workOrderItemAssignments(): HasMany
    {
        return $this->hasMany(WorkOrderItemMechanic::class);
    }

    public function fullName(): string
    {
        return trim(($this->user?->first_name ?? '') . ' ' . ($this->user?->last_name ?? ''));
    }
}
