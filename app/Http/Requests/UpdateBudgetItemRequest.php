<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\BudgetItemType;

class UpdateBudgetItemRequest extends FormRequest
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
                'sometimes',
                Rule::enum(BudgetItemType::class)
            ],
            'description' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],
            'quantity' => [
                'sometimes',
                'numeric',
                'min:0.01',
            ],
            'unit_price' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
            'notes' => [
                'sometimes',
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
                'enum' => 'El tipo debe ser un valor válido.',
            ],
            'description' => [
                'string' => 'La descripción debe ser una cadena de texto.',
                'max' => 'La descripción no puede exceder los 255 caracteres.',
                'regex' => 'La descripción solo puede contener letras, números, espacios y los siguientes caracteres: . , ; : ( ) - # @ ! ?',
            ],
            'quantity' => [
                'numeric' => 'La cantidad debe ser un número.',
                'min' => 'La cantidad debe ser al menos 0.01.',
            ],
            'unit_price' => [
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
