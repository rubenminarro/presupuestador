<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatiePermission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends SpatiePermission
{
    protected $fillable = [
        'name', 
        'guard_name',
        'description',
        'active',
    ];

    public function canBeDeleted(): bool
    {   
        if ($this->users()->count() > 0) {
            return false;
        }
        return true;

    }
}