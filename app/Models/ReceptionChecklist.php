<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceptionChecklist extends Model
{
    protected $fillable = [
        'reception_id',
        'item',
        'status',
        'notes',
    ];

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReceptionChecklistItem::class);
    }
}
