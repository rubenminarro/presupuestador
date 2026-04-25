<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'document_number' => [
                'required', 
                'string', 
                'min:5', 
                'max:20',
                Rule::unique('clients', 'document_number'),
                'regex:/^[a-zA-Z0-9-]+$/'
            ],
            'first_name' => [
                'required', 
                'string', 
                'min:2', 
                'max:100',
                'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u'
            ],
            'last_name' => [
                'required', 
                'string', 
                'min:2', 
                'max:100',
                'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u'
            ],
            'email' => [
                'required', 
                'email:rfc,dns', 
                'max:50', 
                'lowercase',
                'unique:clients,email',
            ],
            'phone' => [
                'required',
                'regex:/^\+?[1-9]\d{7,14}$/'
            ],
            'notes' => [
                'nullable', 
                'string', 
                'max:1000',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document_number' => [
                'required' => 'El número de documento es obligatorio.',
                'string' => 'El número de documento debe tener el formato correcto.',
                'min'      => 'El número de documento debe tener al menos 5 caracteres.',
                'max'      => 'El número de documento no debe tener más de 20 caracteres.',
                'unique' => 'Este número de documento ya está en uso.',
                'regex'  => 'El número de documento solo puede contener letras, números y guiones.',
            ],
            'first_name' => [
                'required' => 'El nombre es obligatorio.',
                'string'   => 'El nombre debe tener el formato correcto.',
                'min'      => 'El nombre debe tener al menos 2 caracteres.',
                'max'      => 'El nombre no debe tener más de 100 caracteres.',
                'regex'    => 'El nombre solo puede contener letras y espacios.',
            ],
            'last_name' => [
                'required' => 'El apellido es obligatorio.',
                'string'   => 'El apellido debe tener el formato correcto.',
                'min'      => 'El apellido debe tener al menos 2 caracteres.',
                'max'      => 'El apellido no debe tener más de 100 caracteres.',
                'regex'    => 'El apellido solo puede contener letras y espacios.',
            ],
            'email' => [
                'required'  => 'El correo es obligatorio.',
                'email'     => 'El correo debe tener el formato correcto.',
                'max'       => 'El correo no debe tener más de 50 caracteres.',
                'lowercase' => 'El correo debe estar en minúsculas.',
                'unique'    => 'Este correo ya está en uso.',
            ],
            'phone' => [
                'required' => 'El teléfono es obligatorio.',
                'regex'    => 'El teléfono debe tener un formato válido (opcionalmente con un + al inicio, seguido de 8 a 15 dígitos).',
            ],
            'notes' => [
                'string' => 'Las notas deben tener el formato correcto.',
                'max'    => 'Las notas no deben tener más de 1000 caracteres.',
                'regex'  => 'Las notas solo pueden contener letras, números, espacios y los siguientes caracteres: . , ; : ( ) - # @ ! ?',
            ],
        ];
    }
}