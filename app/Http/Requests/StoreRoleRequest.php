<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'name' => [
                'required', 
                'string',
                'alpha_dash', 
                'lowercase',
                'min:4',
                'max:200',
                Rule::unique('roles', 'name'),
            ],
            'description' => [
                'required',
                'string', 
                'min:10', 
                'max:100', 
                'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u'
            ],
            'guard_name' => [
                'required',
                'string',
                Rule::in(['web', 'api']),
            ],
            'permissions' => [
                'required',
                Rule::exists('permissions', 'name'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name' => [
                'required' => 'El rol es obligatorio.',
                'string' => 'El rol debe tener el formato correcto.',
                'alpha_dash' => 'El rol solo puede contener letras, números y guiones (sin espacios).',
                'lowercase' => 'El rol debe estar en minúsculas.',
                'min' => 'El rol debe tener al menos 4 caracteres.',
                'max' => 'El rol no debe tener más de 200 caracteres.',
                'unique' => 'Este nombre de rol ya está en uso.',
            ],
            'description' => [
                'required' => 'La descripción es obligatoria.',
                'string' => 'La descripción debe tener el formato correcto.',
                'min' => 'La descripción debe tener al menos 10 caracteres.',
                'max' => 'La descripción no debe tener más de 100 caracteres.',
                'regex' => 'La descripción solo puede contener letras y espacios.',
            ],
            'guard_name' => [
                'required' => 'El campo Guard es obligatorio.',
                'string' => 'El campo Guard debe ser una cadena de texto.',
                'in' => 'El campo Guard debe ser "web" o "api".',
            ],
            'permissions' => [
                'required' => 'El campo Permisos es obligatorio.',
                'exists' => 'Al menos uno de los permisos seleccionados no existe.',
            ],
        ];
    }
}
