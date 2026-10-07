<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[\pL\pN\s.,\-()\/]+$/u',
                Rule::unique('part_categories', 'name'),
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name' => [
                'required' => 'El nombre de la categoría es obligatorio.',
                'string' => 'El nombre de la categoría debe ser un texto válido.',
                'min' => 'El nombre de la categoría debe tener al menos 2 caracteres.',
                'max' => 'El nombre de la categoría no debe superar los 100 caracteres.',
                'regex' => 'El nombre de la categoría contiene caracteres no permitidos.',
                'unique' => 'Ya existe una categoría con ese nombre.',
            ],
            'description' => [
                'string' => 'La descripción debe ser un texto válido.',
                'max' => 'La descripción no debe superar los 500 caracteres.',
            ],
            'is_active' => [
                'boolean' => 'El campo activo debe ser verdadero o falso.',
            ],
        ];
    }
}
