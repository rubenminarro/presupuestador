<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\Severity;
use App\Enums\Status;

class StoreDiagnosticItemRequest extends FormRequest
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
            'diagnostic_id' => [
                'required',
                Rule::exists('diagnostics', 'id')
            ],
            'title' => [
                'required',
                'string',
                'max:255',
                'regex:/^[\pL\pN\s]*$/u'
            ],
            'description' => [
                'nullable',
                'string',
                'regex:/^[\pL\pN\s.,;:()\-#@!?%]*$/u'
            ],
            'severity' => [
                'nullable',
                Rule::enum(Severity::class),
            ],
            'status' => [
                'nullable',
                Rule::enum(Status::class)
            ],
            'requires_repair' => [
                'boolean'
            ],
            'requires_replacement' => [
                'boolean'
            ],
            'estimated_cost' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'estimated_time' => [
                'nullable',
                'integer',
                'min:0'
            ],
            'recommendation' => [
                'nullable',
                'string',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'diagnostic_id' => [
                'required' => 'El ID del diagnóstico es obligatorio.',
                'exists' => 'El diagnóstico especificado no existe.',
            ],
            'title' => [
                'required' => 'El título es obligatorio.',
                'string' => 'El título debe ser una cadena de texto.',
                'max' => 'El título no puede exceder los 255 caracteres.',
                'regex' => 'El título solo puede contener letras, números y espacios.',
            ],
            'description' => [
                'string' => 'La descripción debe ser una cadena de texto.',
                'regex' => 'La descripción solo pueden contener letras, números, espacios y los siguientes caracteres: . , ; : ( ) - # @ ! ? %',
            ],
            'severity' => [
                'enum' => 'La severidad debe ser un valor válido.',
            ],
            'status' => [
                'enum' => 'El estado debe ser un valor válido.',
            ],
            'requires_repair' => [
                'boolean' => 'El campo requiere_repair debe ser verdadero o falso.',
            ],
            'requires_replacement' => [
                'boolean' => 'El campo requires_replacement debe ser verdadero o falso.',
            ],
            'estimated_cost' => [
                'numeric' => 'El costo estimado debe ser un número.',
                'min' => 'El costo estimado no puede ser negativo.',
            ],
            'estimated_time' => [
                'integer' => 'El tiempo estimado debe ser un número entero.',
                'min' => 'El tiempo estimado no puede ser negativo.',
            ],
            'recommendation' => [
                'string' => 'La recomendación debe ser una cadena de texto.',
                'regex' => 'La recomendación solo puede contener letras, números, espacios y caracteres de puntuación comunes.',
            ],
        ];
    }

}
