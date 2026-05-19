<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatiePermission;

class Role extends SpatiePermission
{
    protected $fillable = [
        'name', 
        'guard_name',
        'description',
    ];
}