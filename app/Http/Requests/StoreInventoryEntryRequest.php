<?php

namespace App\Http\Requests;

use App\Enums\InventoryMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryEntryRequest extends FormRequest
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
            'type' => [
                'required',
                'string',
                Rule::in(array_map(
                    fn (InventoryMovementType $type) => $type->value,
                    InventoryMovementType::manualEntries()
                )),
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
                'decimal:0,4',
                'min:0',
                'max:99999999.9999',
            ],
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->whereNull('deleted_at'),
            ],
            'document_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'reason' => [
                'nullable',
                'string',
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
            'type' => [
                'required' => 'El tipo de entrada es obligatorio.',
                'string' => 'El tipo de entrada debe ser un texto válido.',
                'in' => 'El tipo de entrada debe ser stock inicial o compra.',
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
                'decimal' => 'El costo unitario admite como máximo 4 decimales.',
                'min' => 'El costo unitario no puede ser negativo.',
                'max' => 'El costo unitario supera el máximo permitido.',
            ],
            'supplier_id' => [
                'integer' => 'El ID del proveedor debe ser un número entero.',
                'exists' => 'El proveedor seleccionado no existe.',
            ],
            'document_number' => [
                'string' => 'El número de documento debe ser un texto válido.',
                'max' => 'El número de documento no debe superar los 50 caracteres.',
            ],
            'reason' => [
                'string' => 'La observación debe ser un texto válido.',
                'max' => 'La observación no debe superar los 1000 caracteres.',
            ],
        ];
    }
}
