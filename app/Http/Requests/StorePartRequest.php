<?php

namespace App\Http\Requests;

use App\Enums\PartUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StorePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[0-9A-Za-z\-_.\/]+$/',
                Rule::unique('parts', 'sku'),
            ],
            'part_number' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[0-9A-Za-z\-_.\/\s]+$/',
            ],
            'name' => [
                'required',
                'string',
                'min:2',
                'max:150',
                'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
                'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u',
            ],
            'brand' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u',
            ],
            'part_category_id' => [
                'required',
                'integer',
                Rule::exists('part_categories', 'id'),
            ],
            'unit' => [
                'required',
                Rule::enum(PartUnit::class),
            ],
            'cost_price' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
            'sale_price' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
            'minimum_stock' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999.999',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'sku' => [
                'string' => 'El SKU debe ser un texto válido.',
                'max' => 'El SKU no debe superar los 100 caracteres.',
                'regex' => 'El SKU contiene caracteres no permitidos.',
                'unique' => 'Ya existe un repuesto con ese SKU.',
            ],
            'part_number' => [
                'string' => 'El número de parte debe ser un texto válido.',
                'max' => 'El número de parte no debe superar los 100 caracteres.',
                'regex' => 'El número de parte contiene caracteres no permitidos.',
            ],
            'name' => [
                'required' => 'El nombre del repuesto es obligatorio.',
                'string' => 'El nombre del repuesto debe ser un texto válido.',
                'min' => 'El nombre del repuesto debe tener al menos 2 caracteres.',
                'max' => 'El nombre del repuesto no debe superar los 150 caracteres.',
                'regex' => 'El nombre del repuesto solo puede contener letras y espacios.',
            ],
            'description' => [
                'string' => 'La descripción debe ser un texto válido.',
                'max' => 'La descripción no debe superar los 1000 caracteres.',
                'regex' => 'La descripción solo puede contener letras y espacios.',
            ],
            'brand' => [
                'string' => 'La marca debe ser un texto válido.',
                'max' => 'La marca no debe superar los 100 caracteres.',
                'regex' => 'La marca solo puede contener letras y espacios.',
            ],
            'part_category_id' => [
                'required' => 'La categoría es obligatoria.',
                'integer' => 'El ID de la categoría debe ser un número entero.',
                'exists' => 'La categoría seleccionada no existe.',
            ],
            'unit' => [
                'required' => 'La unidad de medida es obligatoria.',
                Enum::class => 'La unidad de medida seleccionada no es válida.',
            ],
            'cost_price' => [
                'required' => 'El precio de costo es obligatorio.',
                'numeric' => 'El precio de costo debe ser un número.',
                'min' => 'El precio de costo no puede ser negativo.',
                'max' => 'El precio de costo supera el máximo permitido.',
            ],
            'sale_price' => [
                'required' => 'El precio de venta es obligatorio.',
                'numeric' => 'El precio de venta debe ser un número.',
                'min' => 'El precio de venta no puede ser negativo.',
                'max' => 'El precio de venta supera el máximo permitido.',
            ],
            'minimum_stock' => [
                'numeric' => 'El stock mínimo debe ser un número.',
                'min' => 'El stock mínimo no puede ser negativo.',
                'max' => 'El stock mínimo supera el máximo permitido.',
            ],
        ];
    }
}
