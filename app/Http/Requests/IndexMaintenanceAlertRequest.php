<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexMaintenanceAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:overdue,due_soon'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'status' => [
                'in' => 'El estado debe ser overdue o due_soon.',
            ],
            'per_page' => [
                'integer' => 'La cantidad por página debe ser un número entero.',
                'max' => 'La cantidad por página no puede superar 100.',
            ],
        ];
    }
}
