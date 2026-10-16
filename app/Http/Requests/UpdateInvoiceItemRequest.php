<?php

namespace App\Http\Requests;

use App\Enums\TaxRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateInvoiceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => [
                'sometimes',
                'string',
                'min:2',
                'max:255',
                'regex:/^[\pL\pN\s.,;:()\-\/#%+&"\'°º]+$/u',
            ],
            'tax_rate' => [
                'sometimes',
                'integer',
                Rule::enum(TaxRate::class),
            ],
            'discount' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'description' => [
                'string' => 'La descripción debe ser un texto válido.',
                'min' => 'La descripción debe tener al menos 2 caracteres.',
                'max' => 'La descripción no debe superar los 255 caracteres.',
                'regex' => 'La descripción contiene caracteres no permitidos.',
            ],
            'tax_rate' => [
                'integer' => 'La tasa de IVA debe ser 0, 5 o 10.',
                Enum::class => 'La tasa de IVA debe ser 0, 5 o 10.',
            ],
            'discount' => [
                'numeric' => 'El descuento debe ser un número.',
                'min' => 'El descuento no puede ser negativo.',
                'max' => 'El descuento supera el máximo permitido.',
            ],
        ];
    }
}
