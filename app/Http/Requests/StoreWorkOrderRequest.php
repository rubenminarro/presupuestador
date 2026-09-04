<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mechanic_id' => [
                'required',
                'integer',
                Rule::exists('mechanics', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mechanic_id' => [
                'required' => 'El mecánico es obligatorio.',
                'integer' => 'El ID del mecánico debe ser un número entero.',
                'exists' => 'El mecánico seleccionado no existe.'
            ],
        ];
    }
}