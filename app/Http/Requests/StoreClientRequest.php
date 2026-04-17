<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
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
            'phone' => [
                'required', 
                'regex:/^\+?[1-9]\d{7,14}$/'
            ],
            'email' => [
                'required', 
                'email:rfc,dns',
                'max:50',
                'lowercase',
                Rule::unique('clients', 'email'),
            ],
            'notes' => [
                'nullable', 
                'string', 
                'max:1000', 
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u',
            ],
            'vehicles' => [
                'sometimes',
                'array'
            ],
            'vehicles.*.brand_id' => [
                'required', 
                'exists:brands,id'
            ],
            'vehicles.*.vehicle_model_id' => [
                'required', 
                'exists:vehicle_models,id'
            ],
            'vehicles.*.plate' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Z0-9-]+$/i'
            ],
            'vehicles.*.color' => [
                'nullable',
                'string',
                'max:30'
            ],
            'vehicles.*.notes' => [
                'nullable',
                'string',
                'max:500'
            ],
        ];
    }

    public function messages(): array
    {
        return [
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
            'phone' => [
                'required' => 'El teléfono es obligatorio.',
                'regex'    => 'El teléfono debe tener un formato válido (opcionalmente con un + al inicio, seguido de 8 a 15 dígitos).',
            ],
            'email' => [
                'required'  => 'El correo es obligatorio.',
                'email'     => 'El correo debe tener el formato correcto.',
                'max'       => 'El correo no debe tener más de 50 caracteres.',
                'lowercase' => 'El correo debe estar en minúsculas.',
                'unique'    => 'Este correo ya está en uso.',
            ],
            'notes' => [
                'string' => 'Las notas deben tener el formato correcto.',
                'max'    => 'Las notas no deben tener más de 1000 caracteres.',
                'regex'  => 'Las notas solo pueden contener letras, números, espacios y los siguientes caracteres: . , ; : ( ) - # @ ! ?',
            ],
            'vehicles' => [
                'array'    => 'Los vehículos deben ser un arreglo.',
            ],
            'vehicles.*.brand_id' => [
                'required' => 'La marca es obligatoria.',
                'exists'   => 'La marca seleccionada no es válida.',
            ],
            'vehicles.*.vehicle_model_id' => [
                'required' => 'El modelo es obligatorio.',
                'exists'   => 'El modelo seleccionado no es válido.',
            ],
            'vehicles.*.plate' => [
                'string'   => 'La matrícula debe tener el formato correcto.',
                'max'      => 'La matrícula no debe tener más de 20 caracteres.',
                'regex'    => 'La matrícula solo puede contener letras y números.',
            ],
            'vehicles.*.color' => [
                'string' => 'El color debe tener el formato correcto.',
                'max'    => 'El color no debe tener más de 30 caracteres.',
            ],
            'vehicles.*.notes' => [
                'string' => 'Las notas del vehículo deben tener el formato correcto.',
                'max'    => 'Las notas del vehículo no deben tener más de 500 caracteres.',
            ],
        ];
    }
}
