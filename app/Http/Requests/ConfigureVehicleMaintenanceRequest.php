<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Intervalos propios de un vehículo; null vuelve a los del plan.
 */
class ConfigureVehicleMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'interval_km' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000000'],
            'interval_days' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:3650'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000', 'regex:/^[\pL\pN\s.,;:()\-#@!?%\/]*$/u'],
        ];
    }

    public function messages(): array
    {
        return [
            'interval_km' => [
                'integer' => 'El intervalo en km debe ser un número entero.',
                'min' => 'El intervalo en km debe ser mayor a cero.',
            ],
            'interval_days' => [
                'integer' => 'El intervalo en días debe ser un número entero.',
                'min' => 'El intervalo en días debe ser mayor a cero.',
                'max' => 'El intervalo en días no puede superar 3650 (10 años).',
            ],
            'notes' => [
                'regex' => 'Las notas contienen caracteres no permitidos.',
            ],
        ];
    }
}
