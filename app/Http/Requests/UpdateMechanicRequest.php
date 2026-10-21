<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Enums\MechanicStatus;

class UpdateMechanicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        
        $Idmechanic = $this->route('mechanic');

        return [
            'user_id' => [
                'sometimes',
                Rule::exists('users', 'id'),
                Rule::unique('mechanics', 'user_id')->ignore($Idmechanic),
            ],
            'employee_code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('mechanics', 'employee_code')->ignore($Idmechanic),
            ],
            // Los opcionales del alta (nullable) también se pueden vaciar al editar.
            'specialty' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                'regex:/^[\pL\pN\s.,;:()\-#@!?%]*$/u',
            ],
            'hire_date' => [
                'sometimes',
                'nullable',
                'date',
            ],
            // hour_cost y commission_percentage no admiten null en la base (default 0).
            'hour_cost' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
            'commission_percentage' => [
                'sometimes',
                'numeric',
                'between:0,100',
            ],
            'status' => [
                'sometimes',
                Rule::enum(MechanicStatus::class)
            ],
            'notes' => [
                'sometimes',
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
                'exists' => 'El usuario seleccionado no existe.',
                'unique' => 'El usuario seleccionado ya está asignado a un mecánico.',
            ],
            'employee_code' => [
                'string' => 'El campo código de empleado debe ser una cadena de texto.',
                'max' => 'El campo código de empleado no debe exceder los 50 caracteres.',
                'unique' => 'El código de empleado ya está en uso.',
            ],
            'specialty' => [
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
            'status' => [
                Enum::class => 'El campo estado contiene un valor no válido.',
            ],
            'notes' => [
                'string' => 'El campo notas debe ser una cadena de texto.',
                'regex' => 'El campo notas contiene caracteres no permitidos.',
            ],
        ];
    }
}
