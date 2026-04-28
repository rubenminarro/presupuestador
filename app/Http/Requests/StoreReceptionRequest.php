<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Vehicle;
use App\Enums\FuelLevel;
use App\Enums\Status;

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
                Rule::exists('clients', 'id')->where('active', true),
            ],
            'vehicle_id' => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id')->where('active', true),
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
                Rule::in([
                    FuelLevel::EMPTY->value,
                    FuelLevel::QUARTER->value,
                    FuelLevel::HALF->value,
                    FuelLevel::THREE_QUARTERS->value,
                    FuelLevel::FULL->value,
                ]),
            ],
            'problem_description' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
            'observations' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'status' => [
                'nullable',
                Rule::in([
                    Status::PENDING->value,
                    Status::DIAGNOSIS->value,
                    Status::APPROVED->value,
                    Status::IN_PROGRESS->value,
                    Status::WAITING_PARTS->value,
                    Status::COMPLETED->value,
                    Status::DELIVERED->value,
                    Status::CANCELLED->value,
                ]),
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
            'problem_description' => [
                'required' => 'La descripción del problema es obligatoria.',
                'string' => 'La descripción del problema debe ser una cadena de texto.',
                'min' => 'La descripción del problema debe tener al menos 10 caracteres.',
                'max' => 'La descripción del problema no debe tener más de 5000 caracteres.',
            ],
            'observations' => [
                'string' => 'Las observaciones deben ser una cadena de texto.',
                'max' => 'Las observaciones no deben tener más de 5000 caracteres.',
            ],
            'status' => [
                'in' => 'El estado seleccionado no es válido.',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            
        $clientId = $this->client_id;
            $vehicleId = $this->vehicle_id;

            if (!$clientId || !$vehicleId) {
                return;
            }

            $vehicleBelongsToClient = Vehicle::where('id', $vehicleId)
                ->where('client_id', $clientId)
                ->exists();

            if (!$vehicleBelongsToClient) {
                $validator->errors()->add(
                    'vehicle_id',
                    'El vehículo seleccionado no pertenece al cliente indicado.'
                );
            }
        });
    }
}