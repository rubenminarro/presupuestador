<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceStampRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stamp_number' => [
                'required',
                'digits:8',
                Rule::unique('invoice_stamps')->where(fn ($query) => $query
                    ->where('establishment', $this->input('establishment'))
                    ->where('expedition_point', $this->input('expedition_point'))),
            ],
            'establishment' => ['required', 'digits:3'],
            'expedition_point' => ['required', 'digits:3'],
            'valid_from' => ['required', 'date_format:Y-m-d'],
            'valid_until' => ['required', 'date_format:Y-m-d', 'after_or_equal:valid_from'],
            'next_number' => ['nullable', 'integer', 'min:1', 'max:9999999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'stamp_number' => [
                'required' => 'El número de timbrado es obligatorio.',
                'digits' => 'El número de timbrado debe tener 8 dígitos.',
                'unique' => 'El timbrado ya está registrado para ese establecimiento y punto de expedición.',
            ],
            'establishment' => [
                'required' => 'El establecimiento es obligatorio.',
                'digits' => 'El establecimiento debe tener 3 dígitos (por ejemplo 001).',
            ],
            'expedition_point' => [
                'required' => 'El punto de expedición es obligatorio.',
                'digits' => 'El punto de expedición debe tener 3 dígitos (por ejemplo 001).',
            ],
            'valid_from' => [
                'required' => 'El inicio de vigencia es obligatorio.',
                'date_format' => 'El inicio de vigencia debe tener el formato AAAA-MM-DD.',
            ],
            'valid_until' => [
                'required' => 'El fin de vigencia es obligatorio.',
                'date_format' => 'El fin de vigencia debe tener el formato AAAA-MM-DD.',
                'after_or_equal' => 'El fin de vigencia debe ser igual o posterior al inicio.',
            ],
            'next_number' => [
                'integer' => 'El próximo número debe ser un número entero.',
                'min' => 'El próximo número debe ser al menos 1.',
                'max' => 'El próximo número no puede superar 9999999.',
            ],
            'is_active' => [
                'boolean' => 'El campo activo debe ser verdadero o falso.',
            ],
        ];
    }
}
