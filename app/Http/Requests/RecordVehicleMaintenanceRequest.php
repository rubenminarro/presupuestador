<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordVehicleMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'maintenance_plan_id' => ['required', 'integer', Rule::exists('maintenance_plans', 'id')],
            'done_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000', 'regex:/^[\pL\pN\s.,;:()\-#@!?%\/]*$/u'],
        ];
    }

    public function messages(): array
    {
        return [
            'maintenance_plan_id' => [
                'required' => 'El plan de mantenimiento es obligatorio.',
                'exists' => 'El plan de mantenimiento no existe.',
            ],
            'done_at' => [
                'required' => 'La fecha en que se realizó es obligatoria.',
                'date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
                'before_or_equal' => 'La fecha no puede ser futura.',
            ],
            'mileage' => [
                'integer' => 'El kilometraje debe ser un número entero.',
                'min' => 'El kilometraje no puede ser negativo.',
            ],
            'notes' => [
                'max' => 'Las notas no deben superar los 1000 caracteres.',
                'regex' => 'Las notas contienen caracteres no permitidos.',
            ],
        ];
    }
}
