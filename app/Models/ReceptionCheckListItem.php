<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceptionCheckListItem extends Model
{
    protected $fillable = [
        'reception_check_list_id',
        'check_list_item_id',
        'value',
        'observation',
    ];

    public function checkList(): BelongsTo
    {
        return $this->belongsTo(ReceptionCheckList::class, 'reception_check_list_id');
    }

    public function checkListItem(): BelongsTo
    {
        return $this->belongsTo(CheckListItem::class, 'check_list_item_id');
    }
}
