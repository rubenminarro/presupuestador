<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkOrderPartRequest extends FormRequest
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
            'unit_price' => [
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:9999999999.99',
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
            'unit_price' => [
                'numeric' => 'El precio unitario debe ser un número.',
                'decimal' => 'El precio unitario admite como máximo 2 decimales.',
                'min' => 'El precio unitario no puede ser negativo.',
                'max' => 'El precio unitario supera el máximo permitido.',
            ],
            'notes' => [
                'string' => 'Las notas deben ser un texto válido.',
                'max' => 'Las notas no deben superar los 500 caracteres.',
            ],
        ];
    }
}
