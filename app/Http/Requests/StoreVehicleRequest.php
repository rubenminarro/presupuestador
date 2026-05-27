<?php

namespace App\Http\Requests;

use App\Enums\FuelType;
use App\Enums\TransmissionType;
use App\Enums\VehicleColor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\VehicleModel;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->withoutTrashed(),
            ],
            'brand_id' => [
                'required',
                'integer',
                Rule::exists('brands', 'id'),
            ],
            'vehicle_model_id' => [
                'required',
                'integer',
                Rule::exists('vehicle_models', 'id'),
            ],
            'chassis' => [
                'nullable',
                Rule::requiredIf(function () {
                    return $this->boolean('no_plate');
                }),
                'string', 
                'max:50',
                Rule::unique('vehicles', 'chassis')
            ],
            'plate' => [
                'nullable',
                'required_if:no_plate,false,0',
                'string', 
                'max:20', 
                'regex:/^[A-Z0-9-]+$/i',
                Rule::unique('vehicles', 'plate'),
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
                'max:'.date('Y'),
            ],
            'color' => [
                'nullable',
                Rule::enum(VehicleColor::class),
                'max:30',
            ],
            'engine_number' => [
                'nullable',
                'string',
                'alpha_num:ascii',
                'min:5',
                'max:30',
            ],
            'mileage' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'fuel_type' => [
                'nullable',
                Rule::enum(FuelType::class),
                'max:50',
            ],
            'transmission' => [
                'nullable',
                Rule::enum(TransmissionType::class),
                'max:50',
            ],
            'notes' => [
                'nullable', 
                'string', 
                'max:500', 
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id' => [
                'required' => 'El cliente es obligatorio.',
                'integer' => 'El ID del cliente debe ser un número entero.',
                'exists' => 'El cliente seleccionado no existe.'
            ],
            'brand_id' => [
                'required' => 'La marca es obligatoria.',
                'integer' => 'El ID de la marca debe ser un número entero.',
                'exists' => 'La marca seleccionada no existe.'
            ],
            'vehicle_model_id' => [
                'required' => 'El modelo es obligatorio.',
                'integer' => 'El ID del modelo debe ser un número entero.',
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
                Enum::class => 'El color seleccionado no es válido.',
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
                Enum::class => 'El tipo de combustible seleccionado no es válido.',
                'max' => 'El tipo de combustible no debe tener más de 50 caracteres.',
            ],
            'transmission' => [
                'string' => 'La transmisión debe ser una cadena de texto.',
                Enum::class => 'La transmisión seleccionada no es válida.',
                'max' => 'La transmisión no debe tener más de 50 caracteres.',
            ],
            'notes' => [
                'string' => 'Las notas del vehículo deben ser una cadena de texto.',
                'max' => 'Las notas del vehículo no deben tener más de 500 caracteres.',
                'regex' => 'Las notas del vehículo solo pueden contener letras, números, espacios y los siguientes caracteres: . , ; : ( ) - # @ ! ?',
            ],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            
            $brandId = $this->brand_id;
            $vehicleModelId = $this->vehicle_model_id;

            if ($brandId && $vehicleModelId) {
                $exists = VehicleModel::where('id', $vehicleModelId)
                    ->where('brand_id', $brandId)
                    ->exists();

                if (!$exists) {
                    $validator->errors()->add(
                        'vehicle_model_id',
                        'El modelo seleccionado no pertenece a la marca indicada.'
                    );
                }
            }
        });
    }
}
