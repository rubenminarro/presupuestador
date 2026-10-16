<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason' => [
                'required' => 'El motivo de la anulación es obligatorio.',
                'string' => 'El motivo debe ser un texto válido.',
                'min' => 'El motivo debe tener al menos 3 caracteres.',
                'max' => 'El motivo no debe superar los 1000 caracteres.',
            ],
        ];
    }
}
