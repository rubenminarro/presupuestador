<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryAdjustmentRequest extends FormRequest
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
            'counted_quantity' => [
                'required',
                'numeric',
                'decimal:0,3',
                'min:0',
                'max:999999999.999',
            ],
            'unit_cost' => [
                'nullable',
                'numeric',
                'decimal:0,4',
                'min:0',
                'max:99999999.9999',
            ],
            'reason' => [
                'required',
                'string',
                'min:3',
                'max:1000',
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
            'counted_quantity' => [
                'required' => 'La cantidad contada es obligatoria.',
                'numeric' => 'La cantidad contada debe ser un número.',
                'decimal' => 'La cantidad contada admite como máximo 3 decimales.',
                'min' => 'La cantidad contada no puede ser negativa.',
                'max' => 'La cantidad contada supera el máximo permitido.',
            ],
            'unit_cost' => [
                'numeric' => 'El costo unitario debe ser un número.',
                'decimal' => 'El costo unitario admite como máximo 4 decimales.',
                'min' => 'El costo unitario no puede ser negativo.',
                'max' => 'El costo unitario supera el máximo permitido.',
            ],
            'reason' => [
                'required' => 'El motivo del ajuste es obligatorio.',
                'string' => 'El motivo debe ser un texto válido.',
                'min' => 'El motivo debe tener al menos 3 caracteres.',
                'max' => 'El motivo no debe superar los 1000 caracteres.',
            ],
        ];
    }
}
