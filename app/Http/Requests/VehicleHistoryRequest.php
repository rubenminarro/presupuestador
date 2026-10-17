<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page' => [
                'integer' => 'La cantidad por página debe ser un número entero.',
                'min' => 'La cantidad por página debe ser al menos 1.',
                'max' => 'La cantidad por página no puede superar 100.',
            ],
        ];
    }
}
