<?php

namespace App\Http\Requests;

use App\Enums\AuditEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class IndexAuditLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auditable_type' => ['nullable', 'string', 'max:60', 'regex:/^[a-z_]+$/'],
            'auditable_id' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'event' => ['nullable', Rule::enum(AuditEvent::class)],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'auditable_type' => [
                'string' => 'El tipo debe ser un texto.',
                'max' => 'El tipo no debe tener más de 60 caracteres.',
                'regex' => 'El tipo solo puede contener letras minúsculas y guiones bajos (p. ej. part, invoice, part_supplier).',
            ],
            'auditable_id' => [
                'integer' => 'El ID del registro debe ser un número entero.',
                'min' => 'El ID del registro debe ser mayor a 0.',
            ],
            'user_id' => [
                'integer' => 'El ID del usuario debe ser un número entero.',
                'min' => 'El ID del usuario debe ser mayor a 0.',
            ],
            'event' => [
                Enum::class => 'El evento debe ser ' . implode(', ', AuditEvent::values()) . '.',
            ],
            'date_from' => [
                'date_format' => 'La fecha desde debe tener el formato AAAA-MM-DD.',
            ],
            'date_to' => [
                'date_format' => 'La fecha hasta debe tener el formato AAAA-MM-DD.',
                'after_or_equal' => 'La fecha hasta debe ser igual o posterior a la fecha desde.',
            ],
            'per_page' => [
                'integer' => 'La cantidad por página debe ser un número entero.',
                'min' => 'La cantidad por página debe ser al menos 1.',
                'max' => 'La cantidad por página no puede superar 100.',
            ],
        ];
    }
}
