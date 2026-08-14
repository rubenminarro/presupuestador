<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDiagnosticItemPhotosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photos' => [
                'required', 
                'array',
                'min:1',
                'max:10'
            ],
            'photos.*.file' => [
                'required', 
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120'
            ],
            'photos.*.description' => [
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
            'photos' => [
                'required' => 'Se requiere al menos una foto.',
                'array' => 'El campo de fotos debe ser un arreglo.',
                'min:1' => 'Se requiere al menos una foto.',
                'max:10' => 'No se pueden subir más de 10 fotos a la vez.',
            ],
            'photos.*' => [
                'file.required' => 'Cada foto debe tener un archivo.',
                'file.image' => 'Cada archivo debe ser una imagen válida.',
                'file.mimes' => 'Cada imagen debe ser un archivo de tipo jpg, jpeg, png o webp.',
                'file.max' => 'Cada imagen no debe superar los 5MB.',
                'description.string' => 'La descripción de cada foto debe ser una cadena de texto.',
                'description.regex' => 'La descripción de cada foto solo puede contener letras, números y caracteres especiales permitidos.',
                'description.max' => 'La descripción de cada foto no debe superar los 255 caracteres.',
            ],
        ];
    }
}
