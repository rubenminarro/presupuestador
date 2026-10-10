<?php

namespace App\Models;

use App\Enums\PartStatus;
use App\Enums\PartUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Part extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'sku',
        'part_number',
        'name',
        'description',
        'brand',
        'part_category_id',
        'unit',
        'cost_price',
        'sale_price',
        'minimum_stock',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'unit' => PartUnit::class,
            'status' => PartStatus::class,
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'minimum_stock' => 'decimal:3',
        ];
    }

    /**
     * Puede presupuestarse o agregarse a una OT. Los descontinuados sí,
     * para poder usar el stock remanente.
     */
    public function isUsable(): bool
    {
        return $this->status !== PartStatus::INACTIVE;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PartCategory::class, 'part_category_id');
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)
            ->using(PartSupplier::class)
            ->withPivot(['supplier_part_code', 'last_cost', 'is_preferred'])
            ->withTimestamps();
    }

    /**
     * MVP con un único depósito: un saldo por repuesto.
     */
    public function stock(): HasOne
    {
        return $this->hasOne(PartStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
