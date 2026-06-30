<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMechanicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id'),
                Rule::unique('mechanics', 'user_id'),
            ],
            'employee_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('mechanics', 'employee_code'),
            ],
            'specialty' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[\pL\pN\s.,;:()\-#@!?%]*$/u',
            ],
            'hire_date' => [
                'nullable',
                'date',
            ],
            'hour_cost' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'commission_percentage' => [
                'nullable',
                'numeric',
                'between:0,100',
            ],
            'notes' => [
                'nullable',
                'string',
                'regex:/^[\pL\pN\s.,;:()\-#@!?%]*$/u',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id' => [
                'required' => 'El tipo de ítem es obligatorio.',
                'exists' => 'El usuario seleccionado no existe.',
                'unique' => 'El usuario seleccionado ya está asignado a un mecánico.',
            ],
            'employee_code' => [
                'required' => 'El campo código de empleado es obligatorio.',
                'string' => 'El campo código de empleado debe ser una cadena de texto.',
                'max' => 'El campo código de empleado no debe exceder los 50 caracteres.',
                'unique' => 'El código de empleado ya está en uso.',
            ],
            'specialty' => [
                'required' => 'El campo especialidad es obligatorio.',
                'string' => 'El campo especialidad debe ser una cadena de texto.',
                'max' => 'El campo especialidad no debe exceder los 100 caracteres.',
                'regex' => 'El campo especialidad contiene caracteres no permitidos.',
            ],
            'hire_date' => [
                'date' => 'El campo fecha de contratación debe ser una fecha válida.',
            ],
            'hour_cost' => [
                'numeric' => 'El campo costo por hora debe ser un número.',
                'min' => 'El campo costo por hora no puede ser negativo.',
            ],
            'commission_percentage' => [
                'numeric' => 'El campo porcentaje de comisión debe ser un número.',
                'between' => 'El campo porcentaje de comisión debe estar entre 0 y 100.',
            ],
            'notes' => [
                'string' => 'El campo notas debe ser una cadena de texto.',
                'regex' => 'El campo notas contiene caracteres no permitidos.',
            ],
        ];
    }
}