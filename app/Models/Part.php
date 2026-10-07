<?php

namespace App\Models;

use App\Enums\PartStatus;
use App\Enums\PartUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(PartCategory::class, 'part_category_id');
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)
            ->withPivot(['supplier_part_code', 'last_cost', 'is_preferred'])
            ->withTimestamps();
    }
}
