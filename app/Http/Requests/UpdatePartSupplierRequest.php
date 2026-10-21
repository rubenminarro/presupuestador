<?php

namespace App\Http\Requests;

class UpdatePartSupplierRequest extends AttachPartSupplierRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        
        return [
            // Igual que al vincular: código y último costo se pueden vaciar; el costo con los mismos límites.
            'supplier_part_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9\-_]+$/',
            ],
            'last_cost' => [
                'sometimes',
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
}
