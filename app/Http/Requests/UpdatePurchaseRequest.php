<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'sometimes',
                'integer',
                Rule::exists('suppliers', 'id')->whereNull('deleted_at'),
            ],
            ...collect(StorePurchaseRequest::headerRules())
                ->map(fn (array $rules) => ['sometimes', ...$rules])
                ->all(),
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id' => [
                'integer' => 'El ID del proveedor debe ser un número entero.',
                'exists' => 'El proveedor seleccionado no existe.',
            ],
            ...StorePurchaseRequest::headerMessages(),
        ];
    }
}
