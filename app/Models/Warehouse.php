<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function getDefault(): self
    {
        return static::where('is_default', true)->firstOrFail();
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(PartStock::class);
    }
}
