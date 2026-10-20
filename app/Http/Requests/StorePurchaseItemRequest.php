<?php

namespace App\Http\Requests;

use App\Enums\TaxRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StorePurchaseItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'part_id' => [
                'required',
                'integer',
                Rule::exists('parts', 'id')->whereNull('deleted_at'),
            ],
            'quantity' => [
                'required',
                'numeric',
                'decimal:0,3',
                'gt:0',
                'max:999999999.999',
            ],
            'unit_cost' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:999999999999.99',
            ],
            'tax_rate' => [
                'nullable',
                Rule::enum(TaxRate::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'part_id' => [
                'required' => 'El repuesto es obligatorio.',
                'integer' => 'El ID del repuesto debe ser un número entero.',
                'exists' => 'El repuesto seleccionado no existe.',
            ],
            'quantity' => [
                'required' => 'La cantidad es obligatoria.',
                'numeric' => 'La cantidad debe ser un número.',
                'decimal' => 'La cantidad admite como máximo 3 decimales.',
                'gt' => 'La cantidad debe ser mayor a cero.',
                'max' => 'La cantidad supera el máximo permitido.',
            ],
            'unit_cost' => [
                'required' => 'El costo unitario es obligatorio.',
                'numeric' => 'El costo unitario debe ser un número.',
                'decimal' => 'El costo unitario admite como máximo 2 decimales.',
                'min' => 'El costo unitario no puede ser negativo.',
                'max' => 'El costo unitario supera el máximo permitido.',
            ],
            'tax_rate' => [
                Enum::class => 'La tasa de IVA debe ser 0 (exenta), 5 o 10.',
            ],
        ];
    }
}
