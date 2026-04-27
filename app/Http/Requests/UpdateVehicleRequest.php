<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
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
        
        $vehicleId = $this->route('vehicle')->id;
    
        return [
            'client_id' => [
                'required',
                'exists:clients,id',
            ],
            'brand_id' => [
                'required',
                'exists:brands,id',
            ],
            'brand_model_id' => [
                'required',
                'exists:brand_models,id',
            ],
            'chassis' => [
                'nullable',
                'required_if:no_plate,true,1',
                'string', 
                'max:50',
                Rule::unique('vehicles', 'chassis')->ignore($vehicleId),
            ],
            'plate' => [
                'nullable',
                'required_if:no_plate,false,0',
                'string', 
                'max:20', 
                'regex:/^[A-Z0-9-]+$/i',
                Rule::unique('vehicles', 'plate')->ignore($vehicleId),
            ],
            'no_plate' => [
                'required',
                'boolean',
            ],
            'year' => [
                'nullable',
                'integer',
                'digits:4',
                'min:1900',
                'max:' . date('Y'),
            ],
            'color' => [
                'nullable',
                'string',
                'max:30',
            ],
            'engine_number' => [
                'nullable',
                'string',
                'max:100',
            ],
            'mileage' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'fuel_type' => [
                'nullable',
                'string',
                'max:50',
            ],
            'transmission' => [
                'nullable',
                'string',
                'max:50',
            ],
            'notes' => [
                'nullable', 
                'string', 
                'max:500', 
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],
            'active' => [
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'brand_id' => [
                'required' => 'La marca es obligatoria.',
                'exists' => 'La marca seleccionada no existe.'
            ],
            'brand_model_id' => [
                'required' => 'El modelo es obligatorio.',
                'exists' => 'El modelo seleccionado no existe.'
            ],
            'chassis' => [
                'required_if' => 'El chasis es obligatorio si el vehículo no tiene chapa.',
                'string' => 'El número de chasis debe ser una cadena de texto.',
                'max' => 'El número de chasis no debe tener más de 50 caracteres.',
                'unique' => 'Este número de chasis ya está registrado.',
            ],
            'plate' => [
                'required_if' => 'La matrícula es obligatoria si el vehículo tiene chapa.',
                'string' => 'La matrícula debe ser una cadena de texto.',
                'max' => 'La matrícula no debe tener más de 20 caracteres.',
                'regex' => 'La matrícula solo puede contener letras, números y guiones.',
                'unique' => 'Esta matrícula ya está registrada.',
            ],
            'no_plate' => [
                'required' => 'Debe indicar si el vehículo tiene chapa.',
                'boolean' => 'El campo sin chapa debe ser verdadero o falso.'
            ],
            'year' => [
                'integer' => 'El año debe ser un número entero.',
                'digits' => 'El año debe tener exactamente 4 dígitos.',
                'min' => 'El año no puede ser anterior a 1900.',
                'max' => 'El año no puede ser posterior al año actual.',
            ],
            'color' => [
                'string' => 'El color debe ser una cadena de texto.',
                'max' => 'El color no debe tener más de 30 caracteres.',
            ],
            'engine_number' => [
                'string' => 'El número de motor debe ser una cadena de texto.',
                'max' => 'El número de motor no debe tener más de 100 caracteres.',
            ],
            'mileage' => [
                'integer' => 'El kilometraje debe ser un número entero.',
                'min' => 'El kilometraje no puede ser negativo.',
            ],
            'fuel_type' => [
                'string' => 'El tipo de combustible debe ser una cadena de texto.',
                'max' => 'El tipo de combustible no debe tener más de 50 caracteres.',
            ],
            'transmission' => [
                'string' => 'La transmisión debe ser una cadena de texto.',
                'max' => 'La transmisión no debe tener más de 50 caracteres.',
            ],
            'notes' => [
                'string' => 'Las notas del vehículo deben ser una cadena de texto.',
                'max' => 'Las notas del vehículo no deben tener más de 500 caracteres.',
                'regex' => 'Las notas del vehículo solo pueden contener letras, números, espacios y los siguientes caracteres: . , ; : ( ) - # @ ! ?',
            ],
        ];
    }
}
