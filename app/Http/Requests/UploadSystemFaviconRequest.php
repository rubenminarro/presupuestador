<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

/**
 * Favicon: PNG o ICO cuadrado (16 a 1024 px) o SVG, hasta 512 KB. Un SVG con
 * código ejecutable se rechaza: el archivo queda público en el disco y podría
 * ejecutarse si alguien abre su URL directamente.
 */
class UploadSystemFaviconRequest extends FormRequest
{
    private const MIN_SIZE = 16;

    private const MAX_SIZE = 1024;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'favicon' => ['required', 'file', 'extensions:png,ico,svg', 'mimes:png,ico,svg', 'max:512'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $file = $this->file('favicon');

                if ($validator->errors()->has('favicon') || !$file instanceof UploadedFile) {
                    return;
                }

                $error = strtolower($file->getClientOriginalExtension()) === 'svg'
                    ? $this->svgError($file->getContent())
                    : $this->bitmapError($file->getRealPath());

                if ($error) {
                    $validator->errors()->add('favicon', $error);
                }
            },
        ];
    }

    private function bitmapError(string $path): ?string
    {
        $size = @getimagesize($path);

        if ($size === false) {
            return 'El favicon debe ser una imagen PNG, ICO o SVG válida.';
        }

        [$width, $height] = $size;

        if ($width !== $height) {
            return "El favicon debe ser cuadrado (la imagen mide {$width}×{$height} px).";
        }

        if ($width < self::MIN_SIZE || $width > self::MAX_SIZE) {
            return 'El favicon debe medir entre ' . self::MIN_SIZE . ' y ' . self::MAX_SIZE . ' px de lado.';
        }

        return null;
    }

    private function svgError(string $content): ?string
    {
        if (!str_contains($content, '<svg')) {
            return 'El favicon debe ser una imagen PNG, ICO o SVG válida.';
        }

        if (preg_match('/<(script|foreignObject|iframe|embed|object)\b|\son[a-z]+\s*=|javascript:/i', $content)) {
            return 'El SVG no puede contener scripts, eventos ni enlaces javascript.';
        }

        return null;
    }

    public function messages(): array
    {
        return [
            'favicon' => [
                'required' => 'Seleccioná el archivo del favicon.',
                'file' => 'El favicon debe ser un archivo.',
                'extensions' => 'El favicon debe ser una imagen PNG, ICO o SVG.',
                'mimes' => 'El favicon debe ser una imagen PNG, ICO o SVG.',
                'max' => 'El favicon no debe superar los 512 KB.',
            ],
        ];
    }
}
