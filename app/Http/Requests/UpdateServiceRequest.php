<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_category_id' => [
                'sometimes',
                'integer',
                Rule::exists('service_categories', 'id')->whereNull('deleted_at'),
            ],
            'name' => [
                'sometimes',
                'string',
                'min:2',
                'max:150',
                'regex:/^[\pL\pN\s.,\-\/()"\'+%&]+$/u',
            ],
            'description' => [
                'sometimes',
                'string',
                'max:1000',
                'regex:/^[\pL\pN\s.,;:()\-\/#%+&"\'°º]+$/u',
            ],
            'standard_price' => [
                'sometimes',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:9999999999.99',
            ],
            'estimated_hours' => [
                'sometimes',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:9999.99',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'service_category_id' => [
                'integer' => 'El ID de la categoría debe ser un número entero.',
                'exists' => 'La categoría seleccionada no existe.',
            ],
            'name' => [
                'string' => 'El nombre del servicio debe ser un texto válido.',
                'min' => 'El nombre del servicio debe tener al menos 2 caracteres.',
                'max' => 'El nombre del servicio no debe superar los 150 caracteres.',
                'regex' => 'El nombre del servicio solo puede contener letras, números, espacios y los siguientes caracteres: . , - / ( ) " \' + % &',
            ],
            'description' => [
                'string' => 'La descripción debe ser un texto válido.',
                'max' => 'La descripción no debe superar los 1000 caracteres.',
                'regex' => 'La descripción solo puede contener letras, números, espacios y los siguientes caracteres: . , ; : ( ) - / # % + & " \' ° º',
            ],
            'standard_price' => [
                'numeric' => 'El precio estándar debe ser un número.',
                'decimal' => 'El precio estándar admite como máximo 2 decimales.',
                'min' => 'El precio estándar no puede ser negativo.',
                'max' => 'El precio estándar supera el máximo permitido.',
            ],
            'estimated_hours' => [
                'numeric' => 'Las horas estimadas deben ser un número.',
                'decimal' => 'Las horas estimadas admiten como máximo 2 decimales.',
                'min' => 'Las horas estimadas no pueden ser negativas.',
                'max' => 'Las horas estimadas superan el máximo permitido.',
            ],
        ];
    }
}
