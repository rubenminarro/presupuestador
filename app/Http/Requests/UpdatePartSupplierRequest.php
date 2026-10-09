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
            'supplier_part_code' => [
                'sometimes',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9\-_]+$/',
            ],
            'last_cost' => [
                'sometimes',
                'numeric',
            ],
            'is_preferred' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
