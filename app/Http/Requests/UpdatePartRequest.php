<?php

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\PartUnit;
use Illuminate\Validation\Rules\Enum;

class UpdatePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $partId = $this->route('part')?->id ?? $this->route('part');

        return [
            'sku' => [
                'sometimes',
                'string',
                'max:100',
                'regex:/^[0-9A-Za-z\-_.\/]+$/',
                Rule::unique('parts', 'sku')->ignore($partId),
            ],
            'part_number' => [
                'sometimes',
                'string',
                'max:100',
                'regex:/^[0-9A-Za-z\-_.\/\s]+$/',
            ],
            'name' => [
                'sometimes',
                'string',
                'min:2',
                'max:150',
                'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u',
            ],
            'description' => [
                'sometimes',
                'string',
                'max:1000',
                'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u',
            ],
            'brand' => [
                'sometimes',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u',
            ],
            'part_category_id' => [
                'sometimes',
                'integer',
                Rule::exists('part_categories', 'id'),
            ],
            'unit' => [
                'sometimes',
                Rule::enum(PartUnit::class),
            ],
            'cost_price' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
            'sale_price' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
            'minimum_stock' => [
                'sometimes',
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
                'integer' => 'El ID de la categoría debe ser un número entero.',
                'exists' => 'La categoría seleccionada no existe.',
            ],
            'unit' => [
                Enum::class => 'La unidad de medida seleccionada no es válida.',
            ],
            'cost_price' => [
                'numeric' => 'El precio de costo debe ser un número.',
                'min' => 'El precio de costo no puede ser negativo.',
                'max' => 'El precio de costo supera el máximo permitido.',
            ],
            'sale_price' => [
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
