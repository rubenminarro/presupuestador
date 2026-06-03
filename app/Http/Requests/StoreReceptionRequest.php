<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;
use App\Models\Reception;
use App\Models\Vehicle;
use App\Enums\FuelLevel;
use App\Enums\ServiceType;


class StoreReceptionRequest extends FormRequest
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
                Rule::exists('clients', 'id'),
            ],
            'vehicle_id' => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id'),
            ],
            'service_type' => [
                'required',
                'string',
                Rule::enum(ServiceType::class),
            ],
            'reception_date' => [
                'required',
                'date',
            ],
            'estimated_delivery_date' => [
                'nullable',
                'date',
                'after_or_equal:reception_date',
            ],
            'mileage' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'fuel_level' => [
                'nullable',
                'string',
                Rule::enum(FuelLevel::class),
            ],
            'problem_description' => [
                'nullable', 
                'string', 
                'max:500', 
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u'
            ],
            'observations' => [
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
            'vehicle_id' => [
                'required' => 'El vehículo es obligatorio.',
                'integer' => 'El ID del vehículo debe ser un número entero.',
                'exists' => 'El vehículo seleccionado no existe.'
            ],
            'service_type' => [
                'required' => 'El tipo de servicio es obligatorio.',
                'string' => 'El tipo de servicio debe ser una cadena de texto.',
                Enum::class => 'El tipo de servicio seleccionado no es válido.',
            ],
            'reception_date' => [
                'required' => 'La fecha de recepción es obligatoria.',
                'date' => 'La fecha de recepción debe ser una fecha válida.',
            ],
            'estimated_delivery_date' => [
                'date' => 'La fecha estimada de entrega debe ser una fecha válida.',
                'after_or_equal' => 'La fecha estimada de entrega no puede ser anterior a la fecha de recepción.',
            ],
            'mileage' => [
                'integer' => 'El kilometraje debe ser un número entero.',
                'min' => 'El kilometraje no puede ser negativo.',
            ],
            'fuel_level' => [
                'string' => 'El nivel de combustible debe ser una cadena de texto.',
                Enum::class => 'El nivel de combustible seleccionado no es válido.',
            ],
            'problem_description' => [
                'string' => 'La descripción del problema debe ser una cadena de texto.',
                'max' => 'La descripción del problema no debe tener más de 500 caracteres.',
                'regex' => 'La descripción del problema contiene caracteres no permitidos.',
            ],
            'observations' => [
                'string' => 'Las observaciones deben ser una cadena de texto.',
                'max' => 'Las observaciones no deben tener más de 500 caracteres.',
                'regex' => 'Las observaciones contienen caracteres no permitidos.',
            ],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            
            $clientId = $this->client_id;
            $vehicleId = $this->vehicle_id;

            if (!$clientId || !$vehicleId) {
                return;
            }

            $vehicleBelongsToClient = Vehicle::query()
                ->where('id', $vehicleId)
                ->where('client_id', $clientId)
                ->exists();

            if (! $vehicleBelongsToClient) {
                $validator->errors()->add(
                    'vehicle_id',
                    'El vehículo seleccionado no pertenece al cliente indicado.'
                );
            }

            $hasOpenReception = Reception::query()
                ->where('vehicle_id', $vehicleId)
                ->whereIn('status', [
                    'pending',
                    'diagnosis',
                    'approved',
                    'in_progress',
                    'waiting_parts',
                ])
                ->exists();

            if ($hasOpenReception) {
                $validator->errors()->add(
                    'vehicle_id',
                    'El vehículo ya tiene una recepción activa.'
                );
            }
            
        });
    }
}