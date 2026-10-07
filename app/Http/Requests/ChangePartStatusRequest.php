<?php

namespace App\Http\Requests;

use App\Enums\PartStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangePartStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(PartStatus::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status' => [
                'required' => 'El estado es obligatorio.',
                'enum' => 'El estado seleccionado no es válido.',
            ],
        ];
    }
}
