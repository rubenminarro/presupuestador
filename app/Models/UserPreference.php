<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Preferencias visuales del usuario. No se audita: es personalización de la
 * interfaz, sin datos de negocio ni de seguridad.
 */
class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
