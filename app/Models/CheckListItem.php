<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class CheckListItem extends Model
{
    protected $fillable = [
        'name',
        'type',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /*public function receptionItems(): HasMany
    {
        return $this->hasMany(ReceptionChecklistItem::class);
    }*/
}
