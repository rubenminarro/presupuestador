<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccountsPayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id' => [
                'integer' => 'El ID del proveedor debe ser un número entero.',
                'exists' => 'El proveedor seleccionado no existe.',
            ],
        ];
    }
}
