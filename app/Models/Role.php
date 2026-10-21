<?php

namespace App\Models;

use App\Traits\Auditable;
use Spatie\Permission\Models\Role as SpatiePermission;

class Role extends SpatiePermission
{
    use Auditable;

    protected $fillable = [
        'name', 
        'guard_name',
        'description',
    ];
}