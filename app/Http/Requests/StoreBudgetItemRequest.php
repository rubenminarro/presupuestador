<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\BudgetItemType;

class StoreBudgetItemRequest extends FormRequest
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
            'type' => [
                'required',
                Rule::enum(BudgetItemType::class)
            ],
            'description' => [
                'required',
                'string',
                'max:255',
                'regex:/^[\pL\pN\s.,;:()\-#@!?%]*$/u'
            ],
            'quantity' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],

        ];
    }

    public function messages(): array
    {
        return [
            'type' => [
                'required' => 'El tipo de ítem es obligatorio.',
                'enum' => 'El tipo de ítem debe ser un valor válido.',
            ],
            'description' => [
                'required' => 'La descripción es obligatoria.',
                'string' => 'La descripción debe ser una cadena de texto.',
                'max' => 'La descripción no puede exceder los 255 caracteres.',
                'regex' => 'La descripción solo puede contener letras, números, espacios y los siguientes caracteres: . , ; : ( ) - # @ ! ? %',
            ],
            'quantity' => [
                'required' => 'La cantidad es obligatoria.',
                'numeric' => 'La cantidad debe ser un número.',
                'min' => 'La cantidad debe ser al menos 0.01.',
            ],
            'unit_price' => [
                'required' => 'El precio unitario es obligatorio.',
                'numeric' => 'El precio unitario debe ser un número.',
                'min' => 'El precio unitario no puede ser negativo.',
            ],
            'notes' => [
                'string' => 'Las notas deben ser una cadena de texto.',
                'max' => 'Las notas no pueden exceder los 500 caracteres.',
                'regex' => 'Las notas solo pueden contener letras, números, espacios y los siguientes caracteres: . , ; : ( ) - # @ ! ?',
            ],
        ];
    }
}
