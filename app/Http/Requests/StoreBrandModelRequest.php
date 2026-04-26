<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBrandModelRequest extends FormRequest
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
            'name' => [
                'required',
                'min:2',
                'max:100',
                'string', 
                'regex:/^[\pL\pN\s-]+$/u',
                Rule::unique('brand_models', 'name'),
            ],
            'brand_id' => [
                'required',
                'integer',
                'exists:brands,id',
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
                'regex'    => 'El nombre de la marca solo puede contener letras, números y guiones.',
                'unique'   => 'Esta marca ya existe en el sistema.',
            ],
            'brand_id' => [
                'required' => 'El ID de la marca es obligatorio.',
                'integer'  => 'El ID de la marca debe ser un número entero.',
                'exists'   => 'La marca seleccionada no existe.',
            ],
        ];
    }
}
