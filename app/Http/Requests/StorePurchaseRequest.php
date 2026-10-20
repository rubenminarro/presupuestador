<?php

namespace App\Http\Requests;

use App\Enums\InvoiceCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->whereNull('deleted_at'),
            ],
            ...self::headerRules(),
        ];
    }

    /**
     * Reglas de la cabecera compartidas con UpdatePurchaseRequest.
     */
    public static function headerRules(): array
    {
        return [
            'supplier_document_number' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[0-9A-Za-z\-\/]+$/',
            ],
            'supplier_document_date' => [
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],
            'supplier_stamp_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9]+$/',
            ],
            'condition' => [
                'nullable',
                Rule::enum(InvoiceCondition::class),
            ],
            'due_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id' => [
                'required' => 'El proveedor es obligatorio.',
                'integer' => 'El ID del proveedor debe ser un número entero.',
                'exists' => 'El proveedor seleccionado no existe.',
            ],
            ...self::headerMessages(),
        ];
    }

    public static function headerMessages(): array
    {
        return [
            'supplier_document_number' => [
                'string' => 'El número de factura del proveedor debe ser un texto válido.',
                'max' => 'El número de factura del proveedor no debe superar los 50 caracteres.',
                'regex' => 'El número de factura del proveedor solo puede contener letras, números, guiones y barras.',
            ],
            'supplier_document_date' => [
                'date_format' => 'La fecha de la factura del proveedor debe tener el formato AAAA-MM-DD.',
                'before_or_equal' => 'La fecha de la factura del proveedor no puede ser futura.',
            ],
            'supplier_stamp_number' => [
                'string' => 'El timbrado del proveedor debe ser un texto válido.',
                'max' => 'El timbrado del proveedor no debe superar los 20 caracteres.',
                'regex' => 'El timbrado del proveedor solo puede contener números.',
            ],
            'condition' => [
                Enum::class => 'La condición debe ser cash (contado) o credit (crédito).',
            ],
            'due_date' => [
                'date_format' => 'La fecha de vencimiento debe tener el formato AAAA-MM-DD.',
            ],
            'notes' => [
                'string' => 'Las notas deben ser un texto válido.',
                'max' => 'Las notas no deben superar los 1000 caracteres.',
            ],
        ];
    }
}
