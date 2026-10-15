<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Servicio del catálogo (lista de precios de trabajos del taller).
 */
class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'service_category_id',
        'name',
        'description',
        'standard_price',
        'estimated_hours',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ServiceStatus::class,
            'standard_price' => 'decimal:2',
            'estimated_hours' => 'decimal:2',
        ];
    }

    public function isUsable(): bool
    {
        return $this->status === ServiceStatus::ACTIVE;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }
}
