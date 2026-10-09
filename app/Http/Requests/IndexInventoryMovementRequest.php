<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\InventoryMovementType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class IndexInventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'part_id' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::enum(InventoryMovementType::class)],
            'supplier_id' => ['nullable', 'integer'],
            'created_by' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'part_id' => [
                'integer' => 'El ID del repuesto debe ser un número entero.',
            ],
            'type' => [
                Enum::class => 'El tipo de movimiento no es válido.',
            ],
            'supplier_id' => [
                'integer' => 'El ID del proveedor debe ser un número entero.',
            ],
            'created_by' => [
                'integer' => 'El ID del usuario debe ser un número entero.',
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
