<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mechanic_id' => [
                'sometimes',
                'integer',
                Rule::exists('mechanics', 'id'),
            ],

            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
                'regex:/^[\pL\pN\s\.,;:\-_()¿?!¡]+$/u',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mechanic_id' => [
                'integer' => 'El ID del mecánico debe ser un número entero.',
                'exists' => 'El mecánico seleccionado no existe.'
            ],
            'notes' => [
                'string' => 'Las notas deben ser un texto válido.',
                'max' => 'Las notas no pueden superar los 500 caracteres.',
                'regex' => 'Las notas contienen caracteres no permitidos.',
            ],
        ];
    }
}