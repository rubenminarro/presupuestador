<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiagnosticItemPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'sometimes',
                'required_without_all:description',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'description' => [
                'sometimes',
                'required_without_all:file',
                'nullable',
                'string',
                'regex:/^[\pL\pN\s.,!?-]*$/u',
                'max:255'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file' => [
                'required_without_all' => 'Se requiere al menos un archivo o una descripción.',
                'image' => 'El archivo debe ser una imagen válida.',
                'mimes' => 'La imagen debe ser un archivo de tipo jpg, jpeg, png o webp.',
                'max' => 'La imagen no debe superar los 5MB.',
            ],
            'description' => [
                'required_without_all' => 'Se requiere al menos un archivo o una descripción.',
                'string' => 'La descripción de la foto debe ser una cadena de texto.',
                'regex' => 'La descripción de la foto solo puede contener letras, números y caracteres especiales permitidos.',
                'max' => 'La descripción de la foto no debe superar los 255 caracteres.',
            ],
        ];
    }
}
