<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsumeWorkOrderPartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity_used' => [
                'required',
                'numeric',
                'decimal:0,3',
                'gt:0',
                'max:999999999.999',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity_used' => [
                'required' => 'La cantidad utilizada es obligatoria.',
                'numeric' => 'La cantidad utilizada debe ser un número.',
                'decimal' => 'La cantidad utilizada admite como máximo 3 decimales.',
                'gt' => 'La cantidad utilizada debe ser mayor a cero.',
                'max' => 'La cantidad utilizada supera el máximo permitido.',
            ],
            'notes' => [
                'string' => 'Las notas deben ser un texto válido.',
                'max' => 'Las notas no deben superar los 500 caracteres.',
            ],
        ];
    }
}
