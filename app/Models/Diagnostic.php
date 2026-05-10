<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function reception()
    {
        return $this->belongsTo(Reception::class);
    }

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }
}
