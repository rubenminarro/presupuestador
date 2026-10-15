<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncWorkOrderItemMechanicsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mechanics' => [
                'required',
                'array',
                'min:1',
            ],
            'mechanics.*.mechanic_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('mechanics', 'id')->whereNull('deleted_at'),
            ],
            'mechanics.*.share_percentage' => [
                'nullable',
                'numeric',
                'decimal:0,2',
                'gt:0',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mechanics' => [
                'required' => 'Debe asignar al menos un mecánico.',
                'array' => 'La lista de mecánicos no es válida.',
                'min' => 'Debe asignar al menos un mecánico.',
            ],
            'mechanics.*.mechanic_id' => [
                'required' => 'El mecánico es obligatorio.',
                'integer' => 'El ID del mecánico debe ser un número entero.',
                'distinct' => 'El mecánico está repetido.',
                'exists' => 'El mecánico seleccionado no existe.',
            ],
            'mechanics.*.share_percentage' => [
                'numeric' => 'El porcentaje debe ser un número.',
                'decimal' => 'El porcentaje admite como máximo 2 decimales.',
                'gt' => 'El porcentaje debe ser mayor a cero.',
                'max' => 'El porcentaje no puede superar 100.',
            ],
        ];
    }
}
