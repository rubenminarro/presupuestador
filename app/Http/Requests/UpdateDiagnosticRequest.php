<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\Priority;
use App\Enums\DiagnosticStatus;
use Illuminate\Validation\Rules\Enum;

class UpdateDiagnosticRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reception_id' => [
                'sometimes',
                Rule::exists('receptions', 'id')
            ],
            // Los opcionales del alta (nullable) también se pueden vaciar al editar.
            'mechanic_id' => [
                'sometimes',
                'nullable',
                Rule::exists('mechanics', 'id')->withoutTrashed()
            ],
            'customer_complaint' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],
            'diagnosis' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],
            'recommendation' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],
            // priority no admite null en la base (default medium).
            'priority' => [
                'sometimes',
                'required',
                Rule::enum(Priority::class),
            ],
            'status' => [
                'sometimes',
                Rule::enum(DiagnosticStatus::class)
            ],
            'requires_parts' => [
                'sometimes',
                'boolean'
            ],
            'requires_repair' => [
                'sometimes',
                'boolean'
            ],
            'diagnosed_at' => [
                'sometimes',
                'date'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reception_id' => [
                'exists' => 'El ID de recepción no existe en la base de datos.',
            ],
            'mechanic_id' => [
                'exists' => 'El ID del mecánico no existe en la base de datos.',
            ],
            'customer_complaint' => [
                'regex' => 'La queja del cliente contiene caracteres no permitidos.',
            ],
            'diagnosis' => [
                'regex' => 'El diagnóstico contiene caracteres no permitidos.',
            ],
            'recommendation' => [
                'regex' => 'La recomendación contiene caracteres no permitidos.',
            ],
            'priority' => [
                'required' => 'La prioridad no puede quedar vacía.',
                Enum::class => 'La prioridad debe ser uno de los siguientes: low, medium, high.',
            ],
            'status' => [
                Enum::class => 'El estado debe ser uno de los siguientes: pending, in_progress, completed, approved, rejected.',
            ],
            'requires_parts' => [
                'boolean' => 'El campo requiere piezas debe ser un valor booleano.',
            ],
            'requires_repair' => [
                'boolean' => 'El campo requiere reparación debe ser un valor booleano.',
            ],
            'diagnosed_at' => [
                'date' => 'La fecha de diagnóstico debe ser una fecha válida.',
            ],
        ];
    }
}
