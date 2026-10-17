<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan de mantenimiento preventivo: cada cuántos km y/o días.
 */
class MaintenancePlan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'service_id',
        'interval_km',
        'interval_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'interval_km' => 'integer',
            'interval_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function vehicleMaintenances(): HasMany
    {
        return $this->hasMany(VehicleMaintenance::class);
    }
}
