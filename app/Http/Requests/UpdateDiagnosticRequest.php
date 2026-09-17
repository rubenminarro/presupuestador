<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\Priority;
use App\Enums\DiagnosticStatus;

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
            'mechanic_id' => [
                'sometimes',
                Rule::exists('users', 'id')
            ],
            'customer_complaint' => [
                'sometimes',
                'string',
                'max:1000',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],
            'diagnosis' => [
                'sometimes',
                'string',
                'max:1000',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],
            'recommendation' => [
                'sometimes',
                'string',
                'max:1000',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],
            'priority' => [
                'sometimes',
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
                'enum' => 'La prioridad debe ser uno de los siguientes: low, medium, high.',
            ],
            'status' => [
                'enum' => 'El estado debe ser uno de los siguientes: pending, in_progress, completed, approved, rejected.',
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
