<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends StoreSupplierRequest
{
    public function rules(): array
    {
        $supplierId = $this->route('supplier')?->id ?? $this->route('supplier');

        $rules = parent::rules();

        $rules['name'] = ['sometimes', 'required', 'string', 'min:2', 'max:150'];

        $rules['tax_id'] = [
            'sometimes',
            'nullable',
            'string',
            'max:30',
            'regex:/^[0-9A-Za-z\-]+$/',
            Rule::unique('suppliers', 'tax_id')->ignore($supplierId),
        ];

        foreach (['contact_name', 'phone', 'email', 'address', 'notes'] as $field) {
            array_unshift($rules[$field], 'sometimes');
        }

        return $rules;
    }
}
