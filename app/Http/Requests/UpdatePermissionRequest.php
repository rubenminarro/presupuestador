<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => strtolower(trim($this->name)),
        ]);
    }

    public function rules(): array
    {
        
        $permissionId = $this->route('permission');
        
        return [
            'name' => [
                'sometimes',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+\.[a-z0-9]+$/', 
                Rule::unique('permissions', 'name')->ignore($permissionId),
            ],
            'guard_name' => [
                'sometimes',
                'string',
                Rule::in(['web', 'api']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name' => [
                'string'   => 'El nombre del permiso debe ser una cadena de texto.',
                'max'      => 'El nombre del permiso no puede exceder los 100 caracteres.',
                'regex'    => 'El formato del permiso debe ser "recurso.accion" (ej: citas.ver).',
            ],
            'guard_name' => [
                'string'   => 'El entorno de programación debe ser una cadena de texto.',
                'in' => 'El entorno de programación seleccionado no es válido (debe ser web o api).',
            ],
        ];
    }
}
