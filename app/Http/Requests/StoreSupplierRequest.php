<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
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
                'min:2',
                'max:150',
            ],
            'tax_id' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[0-9A-Za-z\-]+$/',
                Rule::unique('suppliers', 'tax_id'),
            ],
            'contact_name' => [
                'nullable',
                'string',
                'max:150',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[0-9+\-\s()]+$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
            ],
            'address' => [
                'nullable',
                'string',
                'max:255',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name' => [
                'required' => 'El nombre del proveedor es obligatorio.',
                'string' => 'El nombre del proveedor debe ser un texto válido.',
                'min' => 'El nombre del proveedor debe tener al menos 2 caracteres.',
                'max' => 'El nombre del proveedor no debe superar los 150 caracteres.',
            ],
            'tax_id' => [
                'string' => 'El RUC debe ser un texto válido.',
                'max' => 'El RUC no debe superar los 30 caracteres.',
                'regex' => 'El RUC solo puede contener letras, números y guiones.',
                'unique' => 'Ya existe un proveedor con ese RUC.',
            ],
            'contact_name' => [
                'string' => 'El nombre de contacto debe ser un texto válido.',
                'max' => 'El nombre de contacto no debe superar los 150 caracteres.',
            ],
            'phone' => [
                'string' => 'El teléfono debe ser un texto válido.',
                'max' => 'El teléfono no debe superar los 30 caracteres.',
                'regex' => 'El teléfono contiene caracteres no permitidos.',
            ],
            'email' => [
                'email' => 'El correo electrónico no es válido.',
                'max' => 'El correo electrónico no debe superar los 150 caracteres.',
            ],
            'address' => [
                'string' => 'La dirección debe ser un texto válido.',
                'max' => 'La dirección no debe superar los 255 caracteres.',
            ],
            'notes' => [
                'string' => 'Las notas deben ser un texto válido.',
                'max' => 'Las notas no deben superar los 500 caracteres.',
            ],
            'is_active' => [
                'boolean' => 'El campo activo debe ser verdadero o falso.',
            ],
        ];
    }
}
