<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckListItem extends Model
{
    
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'required'
    ];

    public function receptionItems(): HasMany
    {
        return $this->hasMany(ReceptionCheckListItem::class);
    }
}
