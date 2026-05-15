<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticItemPhoto extends Model
{
    protected $fillable = [
        'diagnostic_item_id',
        'path',
        'original_name',
        'description',
    ];

    public function diagnosticItem(): BelongsTo
    {
        return $this->belongsTo(DiagnosticItem::class);
    }
}
