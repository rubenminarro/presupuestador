<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\Priority;
use App\Enums\Status;

class StoreDiagnosticRequest extends FormRequest
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
            'status' => [
                'nullable',
                Rule::enum(Status::class)
            ],
            'requires_parts' => [
                'boolean'
            ],
            'requires_repair' => [
                'boolean'
            ],
            'diagnosed_at' => [
                'nullable',
                'date'
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
