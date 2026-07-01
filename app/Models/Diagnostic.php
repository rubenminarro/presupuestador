<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Diagnostic extends Model
{
    protected $fillable = [
        'reception_id',
        'mechanic_id',
        'customer_complaint',
        'diagnosis',
        'recommendation',
        'priority',
        'status',
        'requires_parts',
        'requires_repair',
        'diagnosed_at',
    ];

    protected $casts = [
        'requires_parts' => 'boolean',
        'requires_repair' => 'boolean',
        'diagnosed_at' => 'datetime',
    ];

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Mechanic::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DiagnosticItem::class);
    }
}
