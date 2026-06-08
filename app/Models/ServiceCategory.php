<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCategory extends Model
{
    
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
    ];

    public function checkListItems(): BelongsToMany
    {
        return $this->belongsToMany(
            CheckListItem::class,
            'check_list_item_service_category'
        );
    }
}
