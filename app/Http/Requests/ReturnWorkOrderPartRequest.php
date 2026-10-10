<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReturnWorkOrderPartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => [
                'required',
                'numeric',
                'decimal:0,3',
                'gt:0',
                'max:999999999.999',
            ],
            'reason' => [
                'required',
                'string',
                'min:3',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity' => [
                'required' => 'La cantidad a devolver es obligatoria.',
                'numeric' => 'La cantidad a devolver debe ser un número.',
                'decimal' => 'La cantidad a devolver admite como máximo 3 decimales.',
                'gt' => 'La cantidad a devolver debe ser mayor a cero.',
                'max' => 'La cantidad a devolver supera el máximo permitido.',
            ],
            'reason' => [
                'required' => 'El motivo de la devolución es obligatorio.',
                'string' => 'El motivo debe ser un texto válido.',
                'min' => 'El motivo debe tener al menos 3 caracteres.',
                'max' => 'El motivo no debe superar los 1000 caracteres.',
            ],
        ];
    }
}
