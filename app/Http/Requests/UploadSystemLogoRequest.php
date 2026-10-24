<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadSystemLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Solo PNG y JPG: los formatos que dompdf dibuja bien en los PDF.
        return [
            'logo' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'logo' => [
                'required' => 'Seleccioná el archivo del logo.',
                'file' => 'El logo debe ser un archivo.',
                'image' => 'El logo debe ser una imagen PNG o JPG.',
                'mimes' => 'El logo debe ser una imagen PNG o JPG.',
                'max' => 'El logo no debe superar los 2 MB.',
            ],
        ];
    }
}
