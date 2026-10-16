<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IssueInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Opcional: si se omite se usa el único timbrado activo y vigente.
            'invoice_stamp_id' => ['nullable', 'integer', Rule::exists('invoice_stamps', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_stamp_id' => [
                'integer' => 'El ID del timbrado debe ser un número entero.',
                'exists' => 'El timbrado seleccionado no existe.',
            ],
        ];
    }
}
