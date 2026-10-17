<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenancePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[\pL\pN\s.,()\-\/]+$/u', Rule::unique('maintenance_plans', 'name')],
            'description' => ['nullable', 'string', 'max:1000', 'regex:/^[\pL\pN\s.,;:()\-#@!?%\/]*$/u'],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')->withoutTrashed()],
            'interval_km' => ['nullable', 'integer', 'min:1', 'max:1000000', 'required_without:interval_days'],
            'interval_days' => ['nullable', 'integer', 'min:1', 'max:3650', 'required_without:interval_km'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name' => [
                'required' => 'El nombre del plan es obligatorio.',
                'min' => 'El nombre del plan debe tener al menos 3 caracteres.',
                'max' => 'El nombre del plan no debe superar los 100 caracteres.',
                'regex' => 'El nombre del plan contiene caracteres no permitidos.',
                'unique' => 'Ya existe un plan con ese nombre.',
            ],
            'description' => [
                'max' => 'La descripción no debe superar los 1000 caracteres.',
                'regex' => 'La descripción contiene caracteres no permitidos.',
            ],
            'service_id' => [
                'integer' => 'El servicio debe ser un ID válido.',
                'exists' => 'El servicio seleccionado no existe.',
            ],
            'interval_km' => [
                'integer' => 'El intervalo en km debe ser un número entero.',
                'min' => 'El intervalo en km debe ser mayor a cero.',
                'max' => 'El intervalo en km es demasiado grande.',
                'required_without' => 'Indique un intervalo en km o en días.',
            ],
            'interval_days' => [
                'integer' => 'El intervalo en días debe ser un número entero.',
                'min' => 'El intervalo en días debe ser mayor a cero.',
                'max' => 'El intervalo en días no puede superar 3650 (10 años).',
                'required_without' => 'Indique un intervalo en km o en días.',
            ],
            'is_active' => [
                'boolean' => 'El estado debe ser verdadero o falso.',
            ],
        ];
    }
}
