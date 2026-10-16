<?php

namespace App\Http\Requests;

use App\Enums\InvoiceCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => [
                'sometimes',
                'string',
                'min:2',
                'max:150',
                'regex:/^[\pL\pN\s.,\-&()\']+$/u',
            ],
            'customer_tax_id' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
                'regex:/^[0-9A-Za-z\-]+$/',
            ],
            'customer_address' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'regex:/^[\pL\pN\s.,#\-\/°º&()\']+$/u',
            ],
            'condition' => [
                'sometimes',
                Rule::enum(InvoiceCondition::class),
            ],
            'due_date' => [
                'sometimes',
                'nullable',
                'date_format:Y-m-d',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name' => [
                'string' => 'El nombre o razón social debe ser un texto válido.',
                'min' => 'El nombre o razón social debe tener al menos 2 caracteres.',
                'max' => 'El nombre o razón social no debe superar los 150 caracteres.',
                'regex' => 'El nombre o razón social contiene caracteres no permitidos.',
            ],
            'customer_tax_id' => [
                'string' => 'El RUC o CI debe ser un texto válido.',
                'max' => 'El RUC o CI no debe superar los 30 caracteres.',
                'regex' => 'El RUC o CI solo puede contener letras, números y guiones.',
            ],
            'customer_address' => [
                'string' => 'La dirección debe ser un texto válido.',
                'max' => 'La dirección no debe superar los 255 caracteres.',
                'regex' => 'La dirección contiene caracteres no permitidos.',
            ],
            'condition' => [
                Enum::class => 'La condición debe ser cash (contado) o credit (crédito).',
            ],
            'due_date' => [
                'date_format' => 'La fecha de vencimiento debe tener el formato AAAA-MM-DD.',
            ],
        ];
    }
}
