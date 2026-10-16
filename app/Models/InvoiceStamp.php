<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Timbrado: autoriza la numeración de un establecimiento y punto de
 * expedición durante su vigencia.
 */
class InvoiceStamp extends Model
{
    public const MAX_NUMBER = 9999999;

    protected $fillable = [
        'stamp_number',
        'establishment',
        'expedition_point',
        'valid_from',
        'valid_until',
        'next_number',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
            'next_number' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeValidOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query->whereDate('valid_from', '<=', $date)->whereDate('valid_until', '>=', $date);
    }

    public function isValidOn(CarbonInterface $date): bool
    {
        return $this->valid_from->lte($date->copy()->startOfDay())
            && $this->valid_until->gte($date->copy()->startOfDay());
    }

    public function formatNumber(int $sequence): string
    {
        return sprintf('%s-%s-%07d', $this->establishment, $this->expedition_point, $sequence);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
