<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceptionPhoto extends Model
{
    protected $fillable = [
        'reception_id',
        'path',
        'original_name',
        'description',
    ];

    public function reception()
    {
        return $this->belongsTo(Reception::class);
    }
}
