<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdatePartRequest extends StorePartRequest
{
    public function rules(): array
    {
        $partId = $this->route('part')?->id ?? $this->route('part');

        $rules = parent::rules();

        $rules['sku'] = [
            'nullable',
            'string',
            'max:100',
            'regex:/^[0-9A-Za-z\-_.\/]+$/',
            Rule::unique('parts', 'sku')->ignore($partId),
        ];

        foreach ($rules as $field => $fieldRules) {
            array_unshift($rules[$field], 'sometimes');
        }

        return $rules;
    }
}
