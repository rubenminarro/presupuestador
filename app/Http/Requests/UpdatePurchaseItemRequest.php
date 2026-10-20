<?php

namespace App\Http\Requests;

class UpdatePurchaseItemRequest extends StorePurchaseItemRequest
{
    public function rules(): array
    {
        return collect(parent::rules())
            ->map(fn (array $rules) => ['sometimes', ...array_values(array_filter($rules, fn ($rule) => $rule !== 'required'))])
            ->all();
    }
}
