<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReceptionChecklistRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            
            'reception_id' => [
                'sometimes',
                'integer',
                Rule::exists('receptions', 'id')
                    ->where('active', true),
            ],
            'item' => [
                'sometimes',
                'string',
                'min:3',
                'max:150',
            ],
            'status' => [
                'sometimes',
                Rule::in([
                    'good',
                    'damaged',
                    'missing',
                    'observed',
                ]),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reception_id' => [
                'integer' => 'El ID de la recepción debe ser un número entero.',
                'exists' => 'La recepción seleccionada no es válida o no está activa.',
            ],
            'item' => [
                'string' => 'El ítem debe ser una cadena de texto.',
                'min' => 'El ítem debe tener al menos :min caracteres.',
                'max' => 'El ítem no puede exceder los :max caracteres.',
            ],
            'status' => [
                'in' => 'El estado seleccionado no es válido.',
            ],
            'notes' => [
                'string' => 'Las notas deben ser una cadena de texto.',
                'max' => 'Las notas no pueden exceder los :max caracteres.',
            ],
        ];
    }
}
