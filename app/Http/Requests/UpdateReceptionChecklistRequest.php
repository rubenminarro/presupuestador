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
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.check_list_item_id' => [
                'required', 
                Rule::exists('check_list_items', 'id')
            ],
            'items.*.value' => [
                'nullable', 
                'string'
            ],
            'items.*.observation' => [
                'nullable', 
                'string', 
                'max:500',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'item' => [
                'required' => 'Debe indicar el ítem inspeccionado.',
                'array' => 'El ítem debe ser un arreglo de objetos.',
                'min' => 'El ítem debe tener al menos :min caracteres.',
            ],
            'items.*.check_list_item_id' => [
                'required' => 'Debe indicar el ID del ítem de checklist.',
                'exists' => 'El ID del ítem de checklist no es válido.',
            ],
             'items.*.value' => [
                'string' => 'El valor del ítem debe ser una cadena de texto.',
                'min' => 'El valor del ítem debe tener al menos :min caracteres.',
                'max' => 'El valor del ítem no debe exceder los :max caracteres.',
            ],
            'items.*.observation' => [
                'string' => 'La observación del ítem debe ser una cadena de texto.',
                'max' => 'La observación del ítem no debe exceder los :max caracteres.',
                'regex' => 'La observación del ítem contiene caracteres no permitidos.',
            ],
        ];
    }
}
