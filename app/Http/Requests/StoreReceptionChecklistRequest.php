<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReceptionChecklistRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('receptions', 'id')
                    ->where('active', true),
            ],
            'item' => [
                'required',
                'string',
                'min:3',
                'max:150',
            ],
            'status' => [
                'required',
                Rule::in([
                    'good',
                    'damaged',
                    'missing',
                    'observed',
                ]),
            ],'notes' => [
                'nullable',
                'string',
                'max:2000',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reception_id' => [
                'required' => 'Debe indicar la recepción.',
                'integer' => 'El ID de la recepción debe ser un número entero.',
                'exists' => 'La recepción seleccionada no es válida o no está activa.',
            ],
            'item' => [
                'required' => 'Debe indicar el ítem inspeccionado.',
                'string' => 'El ítem debe ser una cadena de texto.',
                'min' => 'El ítem debe tener al menos :min caracteres.',
                'max' => 'El ítem no puede exceder los :max caracteres.',
            ],
            'status' => [
                'required' => 'Debe indicar el estado del ítem.',
                'in' => 'El estado seleccionado no es válido.',
            ],
            'notes' => [
                'string' => 'Las notas deben ser una cadena de texto.',
                'max' => 'Las notas no pueden exceder los :max caracteres.',
                'regex' => 'Las notas solo pueden contener letras, números y los siguientes caracteres especiales: . , ; : ( ) - # @ ! ?',
            ],
        ];
    }
}
