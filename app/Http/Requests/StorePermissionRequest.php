<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => strtolower(trim($this->name)),
            'api' => strtolower(trim($this->api)),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+\.[a-z0-9]+$/', 
                Rule::unique('permissions', 'name'),
            ],
            'guard_name' => [
                'required',
                'string',
                Rule::in(['web', 'api']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name' => [
                'required' => 'El nombre del permiso es obligatorio.',
                'string'   => 'El nombre del permiso debe ser una cadena de texto.',
                'max'      => 'El nombre del permiso no puede exceder los 100 caracteres.',
                'regex'    => 'El formato del permiso debe ser "recurso.accion" (ej: citas.ver).',
                'unique'   => 'Este permiso ya existe en el sistema.',
            ],
            'guard_name' => [
                 'required' => 'El entorno de programación es obligatorio.',
                 'string'   => 'El entorno de programación debe ser una cadena de texto.',
                 'in'       => 'El entorno de programación seleccionado no es válido (debe ser web o api).',
            ],
        ];
    }
}
