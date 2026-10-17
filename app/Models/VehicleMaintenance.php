<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Seguimiento de un plan en un vehículo: último realizado y próximo vencimiento.
 */
class VehicleMaintenance extends Model
{
    protected $fillable = [
        'vehicle_id',
        'maintenance_plan_id',
        'interval_km',
        'interval_days',
        'last_done_at',
        'last_done_mileage',
        'work_order_id',
        'next_due_at',
        'next_due_mileage',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'interval_km' => 'integer',
            'interval_days' => 'integer',
            'last_done_at' => 'date',
            'last_done_mileage' => 'integer',
            'next_due_at' => 'date',
            'next_due_mileage' => 'integer',
        ];
    }

    /**
     * Intervalos efectivos: los propios del vehículo o, si no hay, los del plan.
     */
    public function effectiveIntervalKm(): ?int
    {
        return $this->interval_km ?? $this->plan?->interval_km;
    }

    public function effectiveIntervalDays(): ?int
    {
        return $this->interval_days ?? $this->plan?->interval_days;
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class, 'maintenance_plan_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class)->withTrashed();
    }
}
