<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\Status;

class UpdateBudgetRequest extends FormRequest
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
            'status' => [
                'sometimes',
                Rule::enum(Status::class)
            ],
            'notes' => [
                'sometimes',
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
            'status' => [
                'enum' => 'El estado debe ser un valor válido.',
            ],
            'notes' => [
                'string' => 'Las notas deben ser una cadena de texto.',
                'max' => 'Las notas no pueden exceder los 500 caracteres.',
                'regex' => 'Las notas solo pueden contener letras, números, espacios y los siguientes caracteres: . , ; : ( ) - # @ ! ?',
            ],
        ];
    }

}
