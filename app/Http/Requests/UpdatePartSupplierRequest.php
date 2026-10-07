<?php

namespace App\Http\Requests;

class UpdatePartSupplierRequest extends AttachPartSupplierRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        unset($rules['supplier_id']);

        $rules['supplier_part_code'] = ['sometimes', ...$rules['supplier_part_code']];
        $rules['last_cost'] = ['sometimes', ...$rules['last_cost']];

        return $rules;
    }
}
