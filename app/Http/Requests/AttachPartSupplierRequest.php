<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachPartSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->whereNull('deleted_at'),
            ],
            'supplier_part_code' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9\-_]+$/',
            ],
            'last_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
            'is_preferred' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id' => [
                'required' => 'El proveedor es obligatorio.',
                'integer' => 'El ID del proveedor debe ser un número entero.',
                'exists' => 'El proveedor seleccionado no existe.',
            ],
            'supplier_part_code' => [
                'string' => 'El código del proveedor debe ser un texto válido.',
                'max' => 'El código del proveedor no debe superar los 100 caracteres.',
                'regex' => 'El código del proveedor solo puede contener letras, números, guiones y guiones bajos.',
            ],
            'last_cost' => [
                'numeric' => 'El último costo debe ser un número.',
                'min' => 'El último costo no puede ser negativo.',
                'max' => 'El último costo supera el máximo permitido.',
            ],
            'is_preferred' => [
                'boolean' => 'El campo preferido debe ser verdadero o falso.',
            ],
        ];
    }
}
