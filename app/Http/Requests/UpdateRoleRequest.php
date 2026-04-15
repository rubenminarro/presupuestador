<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roleId = $this->route('role')?->id ?? $this->route('role');

        return [
            'name' => [
                'required', 
                'string',
                'alpha_dash', 
                'lowercase',
                'min:4',
                'max:200',
                Rule::unique('roles', 'name')->ignore($roleId),
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
                'sometimes',
                'array'
            ],
            'permissions.*' => [
                'exists:permissions,name'
            ],
        ];
        
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El rol es obligatorio.',
            'name.string' => 'El rol debe tener el formato correcto.',
            'name.alpha_dash' => 'El rol solo puede contener letras, números y guiones (sin espacios).',
            'name.lowercase' => 'El rol debe estar en minúsculas.',
            'name.min' => 'El rol debe tener al menos 4 caracteres.',
            'name.max' => 'El rol no debe tener más de 200 caracteres.',
            'name.unique' => 'Este nombre de rol ya está en uso.',
            'description.required' => 'La descripción es obligatoria.',
            'description.string' => 'La descripción debe tener el formato correcto.',
            'description.min' => 'La descripción debe tener al menos 10 caracteres.',
            'description.max' => 'La descripción no debe tener más de 100 caracteres.',
            'description.regex' => 'La descripción solo puede contener letras y espacios.',
            'guard_name.required' => 'El campo Guard es obligatorio.',
            'guard_name.string' => 'El campo Guard debe ser una cadena de texto.',
            'guard_name.in' => 'El campo Guard debe ser "web" o "api".',
            'permissions.array' => 'El campo Permisos debe ser un array.',
            'permissions.exists' => 'Al menos uno de los permisos seleccionados no existe.',
        ];
    }
}
