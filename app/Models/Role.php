<?php

namespace App\Models;

use App\Traits\Auditable;
use Spatie\Permission\Models\Role as SpatiePermission;

class Role extends SpatiePermission
{
    use Auditable;

    /** Rol con todos los permisos (`Gate::before` en `AppServiceProvider`). */
    public const ADMINISTRATOR = 'administrador';

    protected $fillable = [
        'name', 
        'guard_name',
        'description',
    ];
}