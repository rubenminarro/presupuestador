<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadDiagnosticItemPhotosRequest extends FormRequest
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
            'photos.*' => [
                'file.required' => 'Cada foto debe tener un archivo.',
                'file.image' => 'Cada archivo debe ser una imagen válida.',
                'file.mimes' => 'Cada imagen debe ser un archivo de tipo jpg, jpeg, png o webp.',
                'file.max' => 'Cada imagen no debe superar los 5MB.',
                'description.string' => 'La descripción de cada foto debe ser una cadena de texto.',
            ],
        ];
    }
}
