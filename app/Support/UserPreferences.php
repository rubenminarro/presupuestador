<?php

namespace App\Support;

/**
 * Catálogo de las preferencias visuales del frontend: valores permitidos y
 * predeterminados. Corresponden a los ajustes de tema de Tabler 1.7
 * (data-bs-theme, data-bs-theme-primary, …); los predeterminados reproducen el
 * aspecto original de la aplicación (barra lateral oscura y expandida).
 */
final class UserPreferences
{
    /**
     * @var array<string, string[]>
     */
    public const OPTIONS = [
        'theme' => ['auto', 'light', 'dark'],
        'theme_primary' => ['blue', 'azure', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'lime', 'green', 'teal', 'cyan', 'inverted'],
        'theme_base' => ['neutral', 'slate', 'gray', 'zinc', 'stone', 'mist'],
        'theme_font' => ['sans-serif', 'serif', 'monospace', 'comic'],
        'theme_radius' => ['0', '0.5', '1', '1.5', '2'],
        'layout' => ['default', 'fluid', 'boxed'],
        'navbar_position' => ['vertical', 'horizontal', 'combined'],
        'navbar_style' => ['default', 'floating'],
        'navbar_theme' => ['dark', 'default', 'primary'],
        'navbar' => ['default', 'sticky'],
        'sidebar' => ['default', 'folded', 'folded-hover'],
    ];

    /**
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'theme' => 'auto',
        'theme_primary' => 'blue',
        'theme_base' => 'neutral',
        'theme_font' => 'sans-serif',
        'theme_radius' => '1',
        'layout' => 'default',
        'navbar_position' => 'vertical',
        'navbar_style' => 'default',
        'navbar_theme' => 'dark',
        'navbar' => 'default',
        'sidebar' => 'default',
    ];

    /**
     * Valores guardados completados con los predeterminados. Se ignoran claves
     * y valores que ya no existan en el catálogo.
     *
     * @param array<string, mixed>|null $stored
     * @return array<string, string>
     */
    public static function resolve(?array $stored): array
    {
        $resolved = self::DEFAULTS;

        foreach ($stored ?? [] as $key => $value) {
            if (isset(self::OPTIONS[$key]) && in_array((string) $value, self::OPTIONS[$key], true)) {
                $resolved[$key] = (string) $value;
            }
        }

        return $resolved;
    }
}
