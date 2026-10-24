<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Configuración general del sistema por clave. Se audita: la cambia solo el
 * administrador y afecta a toda la aplicación (p. ej. el logo).
 */
class SystemSetting extends Model
{
    use Auditable;

    public const LOGO_PATH = 'logo_path';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key): ?string
    {
        return static::where('key', $key)->value('value');
    }
}
