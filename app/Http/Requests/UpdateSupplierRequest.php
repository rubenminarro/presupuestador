<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        
        $supplierId = $this->route('supplier')?->id ?? $this->route('supplier');
    
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:150',
                'regex:/^[\pL\pN\s.,#\-\/°º&()\']+$/u',
            ],
            // Los opcionales del alta (nullable) también se pueden vaciar al editar.
            'tax_id' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
                Rule::unique('suppliers', 'tax_id')->ignore($supplierId),
            ],
            'contact_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:150',
                'regex:/^[\pL\s.\'\-]+$/u',
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
                'regex:/^[0-9+\-\s()]+$/',
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:150',
            ],
            'address' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'regex:/^[\pL\pN\s.,#\-\/°º&()\']+$/u',
            ],
            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name' => [
                'required' => 'El nombre del proveedor no puede quedar vacío.',
                'string' => 'El nombre del proveedor debe ser un texto válido.',
                'min' => 'El nombre del proveedor debe tener al menos 2 caracteres.',
                'max' => 'El nombre del proveedor no debe superar los 150 caracteres.',
                'regex' => 'El nombre del proveedor solo puede contener letras, números, espacios y los siguientes caracteres: . , # - / ° º & ( ) \'',
            ],
            'tax_id' => [
                'string' => 'El RUC debe ser un texto válido.',
                'max' => 'El RUC no debe superar los 30 caracteres.',
                'regex' => 'El RUC solo puede contener letras, números y guiones.',
                'unique' => 'Ya existe un proveedor con ese RUC.',
            ],
            'contact_name' => [
                'string' => 'El nombre de contacto debe ser un texto válido.',
                'max' => 'El nombre de contacto no debe superar los 150 caracteres.',
                'regex' => 'El nombre de contacto solo puede contener letras, espacios y los siguientes caracteres: . \' -',
            ],
            'phone' => [
                'string' => 'El teléfono debe ser un texto válido.',
                'max' => 'El teléfono no debe superar los 30 caracteres.',
                'regex' => 'El teléfono contiene caracteres no permitidos.',
            ],
            'email' => [
                'email' => 'El correo electrónico no es válido.',
                'max' => 'El correo electrónico no debe superar los 150 caracteres.',
            ],
            'address' => [
                'string' => 'La dirección debe ser un texto válido.',
                'max' => 'La dirección no debe superar los 255 caracteres.',
                'regex' => 'La dirección solo puede contener letras, números, espacios y los siguientes caracteres: . , # - / ° º & ( ) \'',
            ],
            'notes' => [
                'string' => 'Las notas deben ser un texto válido.',
                'max' => 'Las notas no deben superar los 500 caracteres.',
                'regex' => 'Las notas solo pueden contener letras, números, espacios y los siguientes caracteres: . , ; : ( ) - # @ ! ? %',
            ],
            'is_active' => [
                'boolean' => 'El campo activo debe ser verdadero o falso.',
            ],
        ];
    }

}
