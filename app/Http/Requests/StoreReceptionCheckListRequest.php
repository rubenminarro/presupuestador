<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReceptionCheckListRequest extends FormRequest
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
                Rule::unique('reception_check_lists', 'reception_id'),
            ],
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
            'reception_id' => [
                'required' => 'Debe indicar la recepción.',
                'integer' => 'El ID de la recepción debe ser un número entero.',
                'exists' => 'La recepción seleccionada no es válida o no está activa.',
                'unique' => 'Ya existe un checklist para esta recepción.',
            ],
            'items' => [
                'required' => 'Debe indicar los ítems inspeccionados.',
                'array' => 'Los ítems deben ser un arreglo de objetos.',
                'min' => 'Debe haber al menos :min ítems inspeccionados.',
            ],
            'items.*.value' => [
                'string' => 'El valor del ítem debe ser una cadena de texto.',
            ],
            'items.*.observation' => [
                'string' => 'La observación del ítem debe ser una cadena de texto.',
                'max' => 'La observación del ítem no debe exceder los :max caracteres.',
                'regex' => 'La observación del ítem contiene caracteres no permitidos.',
            ],
        ];
    }
}
