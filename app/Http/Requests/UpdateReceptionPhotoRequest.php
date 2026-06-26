<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReceptionPhotoRequest extends FormRequest
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
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file' => [
                'sometimes' => 'El campo de archivo es opcional, pero si se proporciona, debe ser una imagen válida.',
                'image' => 'El archivo debe ser una imagen válida.',
                'mimes' => 'La imagen debe ser un archivo de tipo jpg, jpeg, png o webp.',
                'max' => 'La imagen no debe superar los 5MB.',
            ],
            'description.string' => 'La descripción de la foto debe ser una cadena de texto.',
        ];
    }
}
