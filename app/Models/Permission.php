<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends SpatiePermission
{
    // Permitir asignación masiva para tu nueva columna
    protected $fillable = [
        'name',
        'guard_name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Scope para filtrar solo permisos activos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Método para verificar si el permiso se puede borrar (Híbrido)
     */
    public function canBeDeleted(): bool
    {
        return $this->roles()->count() === 0 && $this->users()->count() === 0;
    }
}