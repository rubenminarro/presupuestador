<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\Priority;
use Illuminate\Validation\Rules\Enum;

class StoreDiagnosticRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reception_id' => [
                'required',
                Rule::exists('receptions', 'id')
            ],
            'mechanic_id' => [
                'nullable',
                Rule::exists('users', 'id')
            ],
            'customer_complaint' => [
                'nullable',
                'string',
                'max:1000',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],
            'diagnosis' => [
                'nullable',
                'string',
                'max:1000',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],
            'recommendation' => [
                'nullable',
                'string',
                'max:1000',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],
            'priority' => [
                'nullable',
                Rule::enum(Priority::class),
            ],
            'requires_parts' => [
                'required',
                'boolean'
            ],
            'requires_repair' => [
                'required',
                'boolean'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reception_id' => [
                'required' => 'El ID de recepción es obligatorio.',
                'exists' => 'El ID de recepción no existe en la base de datos.',
            ],
            'mechanic_id' => [
                'exists' => 'El ID del mecánico no existe en la base de datos.',
            ],
            'customer_complaint' => [
                'string' => 'La queja del cliente debe ser una cadena de texto.',
                'max' => 'La queja del cliente no debe exceder los 1000 caracteres.',
                'regex' => 'La queja del cliente contiene caracteres no permitidos.',
            ],
            'diagnosis' => [
                'string' => 'El diagnóstico debe ser una cadena de texto.',
                'max' => 'El diagnóstico no debe exceder los 1000 caracteres.',
                'regex' => 'El diagnóstico contiene caracteres no permitidos.',
            ],
            'recommendation' => [
                'string' => 'La recomendación debe ser una cadena de texto.',
                'max' => 'La recomendación no debe exceder los 1000 caracteres.',
                'regex' => 'La recomendación contiene caracteres no permitidos.',
            ],
            'priority' => [
                Enum::class => 'La prioridad debe ser uno de los siguientes: low, medium, high.',
            ],
            'requires_parts' => [
                'required' => 'El campo requiere piezas es obligatorio.',
                'boolean' => 'El campo requiere piezas debe ser un valor booleano.',
            ],
            'requires_repair' => [
                'required' => 'El campo requiere reparación es obligatorio.',
                'boolean' => 'El campo requiere reparación debe ser un valor booleano.',
            ],
        ];
    }
}
