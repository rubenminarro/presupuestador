<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class IndexSupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'integer'],
            'purchase_id' => ['nullable', 'integer'],
            'method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id' => [
                'integer' => 'El ID del proveedor debe ser un número entero.',
            ],
            'purchase_id' => [
                'integer' => 'El ID de la compra debe ser un número entero.',
            ],
            'method' => [
                Enum::class => 'El medio de pago debe ser cash, card, transfer o check.',
            ],
            'status' => [
                Enum::class => 'El estado debe ser registered o cancelled.',
            ],
            'date_from' => [
                'date_format' => 'La fecha desde debe tener el formato AAAA-MM-DD.',
            ],
            'date_to' => [
                'date_format' => 'La fecha hasta debe tener el formato AAAA-MM-DD.',
                'after_or_equal' => 'La fecha hasta debe ser igual o posterior a la fecha desde.',
            ],
            'per_page' => [
                'integer' => 'La cantidad por página debe ser un número entero.',
                'min' => 'La cantidad por página debe ser al menos 1.',
                'max' => 'La cantidad por página no puede superar 100.',
            ],
        ];
    }
}
