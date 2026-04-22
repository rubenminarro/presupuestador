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
                'required', 'string', 'min:2', 'max:100',
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
            'vehicles' => [
                'required', 
                'min:1', 
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
            'vehicles.*.no_plate' => [
                'required', 
                'boolean'
            ],
            'vehicles.*.plate' => [
                'nullable',
                'required_if:vehicles.*.no_plate,false,0',
                'string', 
                'max:20', 
                'regex:/^[A-Z0-9-]+$/i',
                'unique:vehicles,plate',
            ],'vehicles.*.chassis' => [
                'nullable',
                'required_if:vehicles.*.no_plate,true,1',
                'string', 
                'max:50',
                'unique:vehicles,chassis',
            ],
            'vehicles.*.color' => [
                'nullable', 
                'string', 
                'max:30'
            ],
            
            
            'vehicles.*.notes' => [
                'nullable', 'string', 'max:500', 
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
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
            'vehicles' => [
                'required' => 'Debe registrar al menos un vehículo para crear el cliente.',
                'min'      => 'Debe registrar al menos un vehículo para crear el cliente.',
                'array'    => 'Los vehículos deben ser un arreglo.',
            ],
            'vehicles.*.brand_id' => [
                'required' => 'La marca es obligatoria.',
                'exists' => 'La marca seleccionada no existe.'
            ],
            'vehicles.*.vehicle_model_id' => [
                'required' => 'El modelo es obligatorio.',
                'exists' => 'El modelo seleccionado no existe.'
            ], 
            'vehicles.*.no_plate' => [
                'required' => 'Debe indicar si el vehículo tiene chapa.',
                'boolean' => 'El campo sin chapa debe ser verdadero o falso.'
            ],
            'vehicles.*.plate' => [
                'required_if' => 'La matrícula es obligatoria si el vehículo tiene chapa.',
                'string' => 'La matrícula debe ser una cadena de texto.',
                'max' => 'La matrícula no debe tener más de 20 caracteres.',
                'regex' => 'La matrícula solo puede contener letras, números y guiones.',
                'unique' => 'Esta matrícula ya está registrada.',
            ],
            'vehicles.*.chassis' => [
                'required_if' => 'El chasis es obligatorio si el vehículo no tiene chapa.',
                'string' => 'El número de chasis debe ser una cadena de texto.',
                'max' => 'El número de chasis no debe tener más de 50 caracteres.',
                'unique' => 'Este número de chasis ya está registrado.',
            ],
            'vehicles.*.color' => [
                'string' => 'El color debe ser una cadena de texto.',
                'max' => 'El color no debe tener más de 30 caracteres.',
            ],
            'vehicles.*.notes' => [
                'string' => 'Las notas del vehículo deben ser una cadena de texto.',
                'max' => 'Las notas del vehículo no deben tener más de 500 caracteres.',
                'regex' => 'Las notas del vehículo solo pueden contener letras, números, espacios y los siguientes caracteres: . , ; : ( ) - # @ ! ?',
            ],
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->vehicles) {
            if ($this->vehicles && is_array($this->vehicles)) {
                $this->merge([
                    'vehicles' => collect($this->vehicles)->map(function ($v) {
                        return array_merge($v, [
                            'plate'   => !empty($v['plate']) ? strtoupper(trim($v['plate'])) : null,
                            'chassis' => !empty($v['chassis']) ? strtoupper(trim($v['chassis'])) : null,
                        ]);
                    })->toArray()
                ]);
            }
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->vehicles) return;

            $vehicles = collect($this->vehicles);

            $plates = $vehicles->pluck('plate')->filter();
            if ($plates->duplicates()->isNotEmpty()) {
                $validator->errors()->add('vehicles', 'No se permiten matrículas duplicadas en la lista.');
            }

            $chassis = $vehicles->pluck('chassis')->filter();
            if ($chassis->duplicates()->isNotEmpty()) {
                $validator->errors()->add('vehicles', 'No se permiten números de chasis duplicados en la lista.');
            }
        });
    }
}