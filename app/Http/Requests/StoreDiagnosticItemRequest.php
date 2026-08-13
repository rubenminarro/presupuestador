<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Enums\Severity;
use App\Enums\DiagnosticItemStatus;

class StoreDiagnosticItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'diagnostic_id' => [
                'integer',
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
                'required',
                Rule::enum(Severity::class),
            ],
            'status' => [
                'required',
                Rule::enum(DiagnosticItemStatus::class)
            ],
            'requires_repair' => [
                'required',
                'boolean'
            ],
            'requires_replacement' => [
                'required',
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
                'integer' => 'El ID del diagnóstico debe ser un número entero.',
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
                'required' => 'La severidad es obligatoria.',
                Enum::class => 'La prioridad debe ser uno de los siguientes: low, medium, high, critical.',
            ],
            'status' => [
                'required' => 'El estado es obligatorio.',
                Enum::class => 'El estado debe ser uno de los siguientes: pending, ok, observation, repair_required, replace_required, not_applicable.',
            ],
            'requires_repair' => [
                'required' => 'El campo requiere_repair es obligatorio.',
                'boolean' => 'El campo requiere_repair debe ser verdadero o falso.',
            ],
            'requires_replacement' => [
                'required' => 'El campo requires_replacement es obligatorio.',
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
