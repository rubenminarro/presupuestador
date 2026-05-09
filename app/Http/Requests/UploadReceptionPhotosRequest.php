<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadReceptionPhotosRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photos' => [
                'required', 
                'array'
            ],
            'photos.*.file' => [
                'required', 
                'image', 
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
            'photos.required' => 'Se requiere al menos una foto.',
            'photos.array' => 'El campo de fotos debe ser un arreglo.',
            'photos.*.file.required' => 'Cada foto debe tener un archivo.',
            'photos.*.file.image' => 'Cada archivo debe ser una imagen válida.',
            'photos.*.file.max' => 'Cada imagen no debe superar los 5MB.',
            'photos.*.description.string' => 'La descripción de cada foto debe ser una cadena de texto.',
        ];
    }
}
