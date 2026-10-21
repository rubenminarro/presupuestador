<?php

namespace App\Models;

use App\Traits\Auditable;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use Auditable;

    protected $fillable = [
        'name',
        'guard_name',
    ];

    public static function getGroupedByModule()
    {
        return self::all()
            ->groupBy(fn($p) => explode('.', $p->name)[0])
            ->map(function ($group, $module) {
                return [
                    'module' => $module,
                    'list'   => $group->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->values()
                ];
            })->values();
    }
}