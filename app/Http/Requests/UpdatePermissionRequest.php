<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => strtolower(trim($this->name)),
        ]);
    }

    public function rules(): array
    {
        
        $permissionId = $this->route('permission')?->id ?? $this->route('permission');
        
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+\.[a-z0-9]+$/', 
                Rule::unique('permissions', 'name')->ignore($permissionId),
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
            'name.required' => 'El nombre del permiso es obligatorio.',
            'name.unique'   => 'Este permiso ya existe en el sistema.',
            'name.regex'    => 'El formato del permiso debe ser "recurso.accion" (ej: citas.ver).',
            'guard_name.required' => 'Entorno de programación es obligatorio.',
            'guard_name.in' => 'El entorno de programación seleccionado no es válido (debe ser web o api).',
        ];
    }
}
