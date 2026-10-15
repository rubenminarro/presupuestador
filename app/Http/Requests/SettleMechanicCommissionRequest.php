<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettleMechanicCommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_from' => [
                'required' => 'La fecha desde es obligatoria.',
                'date_format' => 'La fecha desde debe tener el formato AAAA-MM-DD.',
            ],
            'date_to' => [
                'required' => 'La fecha hasta es obligatoria.',
                'date_format' => 'La fecha hasta debe tener el formato AAAA-MM-DD.',
                'after_or_equal' => 'La fecha hasta debe ser igual o posterior a la fecha desde.',
            ],
        ];
    }
}
