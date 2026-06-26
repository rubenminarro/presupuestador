<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceptionPhoto extends Model
{
    protected $fillable = [
        'reception_id',
        'path',
        'original_name',
        'description',
    ];

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }
}
