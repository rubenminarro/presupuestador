<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadReceptionPhotosRequest extends FormRequest
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
                'min:1'
            ],
            'photos.*.file' => [
                'required', 
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120'
            ],
            'photos.*.description' => [
                'nullable', 
                'string'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'photos' => [
                'required' => 'Se requiere al menos una foto.',
                'array' => 'El campo de fotos debe ser un arreglo.',
                'min:1' => 'Se requiere al menos una foto.'
            ],
            'photos.*.file' => [
                'required' => 'Cada foto debe tener un archivo.',
                'image' => 'Cada archivo debe ser una imagen válida.',
                'mimes' => 'Cada imagen debe ser un archivo de tipo jpg, jpeg, png o webp.',
                'max' => 'Cada imagen no debe superar los 5MB.',
                'description.string' => 'La descripción de cada foto debe ser una cadena de texto.',
            ],
        ];
    }
}
