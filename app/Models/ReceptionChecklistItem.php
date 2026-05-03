<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceptionChecklistItem extends Model
{
    protected $fillable = [
        'reception_checklist_id',
        'checklist_item_id',
        'value',
        'observation',
    ];

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class);
    }

    public function receptionChecklist(): BelongsTo
    {
        return $this->belongsTo(ReceptionChecklist::class);
    }
}
