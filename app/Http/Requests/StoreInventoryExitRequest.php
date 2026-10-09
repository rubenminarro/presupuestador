<?php

namespace App\Http\Requests;

use App\Enums\InventoryMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryExitRequest extends FormRequest
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
                    InventoryMovementType::manualExits()
                )),
            ],
            'quantity' => [
                'required',
                'numeric',
                'decimal:0,3',
                'gt:0',
                'max:999999999.999',
            ],
            'supplier_id' => [
                Rule::requiredIf($this->input('type') === InventoryMovementType::SUPPLIER_RETURN->value),
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
            'type' => [
                'required' => 'El tipo de salida es obligatorio.',
                'string' => 'El tipo de salida debe ser un texto válido.',
                'in' => 'El tipo de salida debe ser devolución a proveedor o baja.',
            ],
            'quantity' => [
                'required' => 'La cantidad es obligatoria.',
                'numeric' => 'La cantidad debe ser un número.',
                'decimal' => 'La cantidad admite como máximo 3 decimales.',
                'gt' => 'La cantidad debe ser mayor a cero.',
                'max' => 'La cantidad supera el máximo permitido.',
            ],
            'supplier_id' => [
                'required' => 'El proveedor es obligatorio para una devolución a proveedor.',
                'integer' => 'El ID del proveedor debe ser un número entero.',
                'exists' => 'El proveedor seleccionado no existe.',
            ],
            'document_number' => [
                'string' => 'El número de documento debe ser un texto válido.',
                'max' => 'El número de documento no debe superar los 50 caracteres.',
            ],
            'reason' => [
                'required' => 'El motivo de la salida es obligatorio.',
                'string' => 'El motivo debe ser un texto válido.',
                'min' => 'El motivo debe tener al menos 3 caracteres.',
                'max' => 'El motivo no debe superar los 1000 caracteres.',
            ],
        ];
    }
}
