<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Solo vigencia y estado: el número de timbrado y la numeración no se editan.
 */
class UpdateInvoiceStampRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valid_until' => ['sometimes', 'date_format:Y-m-d'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'valid_until' => [
                'date_format' => 'El fin de vigencia debe tener el formato AAAA-MM-DD.',
            ],
            'is_active' => [
                'boolean' => 'El campo activo debe ser verdadero o falso.',
            ],
        ];
    }
}
