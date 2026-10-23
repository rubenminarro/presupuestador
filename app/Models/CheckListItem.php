<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckListItem extends Model
{
    
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'required',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function receptionItems(): HasMany
    {
        return $this->hasMany(ReceptionCheckListItem::class);
    }

    public function serviceCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            ServiceCategory::class,
            'check_list_item_service_category'
        );
    }
}
