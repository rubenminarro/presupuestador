<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogWorkOrderItemHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // null borra las horas cargadas.
            'hours' => [
                'present',
                'nullable',
                'numeric',
                'decimal:0,2',
                'gt:0',
                'max:999.99',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'hours' => [
                'present' => 'Debe informar las horas (o null para borrarlas).',
                'numeric' => 'Las horas deben ser un número.',
                'decimal' => 'Las horas admiten como máximo 2 decimales.',
                'gt' => 'Las horas deben ser mayores a cero.',
                'max' => 'Las horas superan el máximo permitido.',
            ],
        ];
    }
}
