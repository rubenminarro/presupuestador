<?php

namespace App\Http\Requests;

use App\Support\UserPreferences;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Cambio parcial de preferencias: cada clave es opcional, pero el body debe
 * traer al menos una y solo claves del catálogo.
 */
class UpdateUserPreferencesRequest extends FormRequest
{
    private const LABELS = [
        'theme' => 'El modo de color',
        'theme_primary' => 'El color de acento',
        'theme_base' => 'La paleta de grises',
        'theme_font' => 'La tipografía',
        'theme_radius' => 'El redondeo de bordes',
        'layout' => 'El ancho del contenedor',
        'navbar_position' => 'La posición de la navegación',
        'navbar_style' => 'El estilo de la navegación',
        'navbar_theme' => 'El color de la navegación',
        'navbar' => 'El comportamiento de la barra superior',
        'sidebar' => 'El estado de la barra lateral',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // theme_radius puede llegar como número (0.5); se compara como texto.
        if (is_int($this->theme_radius) || is_float($this->theme_radius)) {
            $this->merge(['theme_radius' => (string) $this->theme_radius]);
        }
    }

    public function rules(): array
    {
        return collect(UserPreferences::OPTIONS)
            ->map(fn (array $values) => ['sometimes', 'required', 'string', Rule::in($values)])
            ->all();
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $keys = array_keys($this->all());

                if ($keys === []) {
                    $validator->errors()->add('preferences', 'Indicá al menos una preferencia.');
                }

                // Una clave desconocida se rechaza para que un error de tipeo no pase inadvertido.
                foreach (array_diff($keys, array_keys(UserPreferences::OPTIONS)) as $key) {
                    $validator->errors()->add($key, "La preferencia {$key} no existe.");
                }
            },
        ];
    }

    public function messages(): array
    {
        $messages = [];

        foreach (UserPreferences::OPTIONS as $key => $values) {
            $message = self::LABELS[$key] . ' debe ser ' . implode(', ', $values) . '.';

            foreach (['required', 'string', 'in'] as $rule) {
                $messages["{$key}.{$rule}"] = $message;
            }
        }

        return $messages;
    }
}
