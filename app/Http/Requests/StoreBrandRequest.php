<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBrandRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'min:2',
                'max:100',
                'string', 
                'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u',
                Rule::unique('brands', 'name'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name' => [
                'required' => 'El nombre de la marca es obligatorio.',
                'string'   => 'El nombre de la marca debe tener el formato correcto.',
                'min'      => 'El nombre de la marca debe tener al menos 2 caracteres.',
                'max'      => 'El nombre de la marca no debe tener más de 100 caracteres.',
                'regex'    => 'El nombre de la marca solo puede contener letras y espacios.',
                'unique'   => 'Esta marca ya existe en el sistema.',
            ],
        ];
    }
}
