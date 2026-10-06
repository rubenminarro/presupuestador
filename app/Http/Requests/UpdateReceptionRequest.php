<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;
use App\Models\Reception;
use App\Models\Vehicle;
use App\Enums\FuelLevel;

class UpdateReceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => [
                'sometimes',
                'integer',
                Rule::exists('clients', 'id'),
            ],
            'vehicle_id' => [
                'sometimes',
                'integer',
                Rule::exists('vehicles', 'id'),
            ],
            'service_category_ids' => [
                'sometimes',
                'array',
                'min:1',
            ],
            'service_category_ids.*' => [
                'integer',
                Rule::exists(
                    'service_categories',
                    'id'
                ),
            ],
            'reception_date' => [
                'sometimes',
                'date',
            ],
            'estimated_delivery_date' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:reception_date',
            ],
            'mileage' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
            'fuel_level' => [
                'sometimes',
                'nullable',
                'string',
                Rule::enum(FuelLevel::class),
            ],
            'problem_description' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u',
            ],
            'observations' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id' => [
                'integer' => 'El ID del cliente debe ser un número entero.',
                'exists' => 'El cliente seleccionado no existe.'
            ],
            'vehicle_id' => [
                'integer' => 'El ID del vehículo debe ser un número entero.',
                'exists' => 'El vehículo seleccionado no existe.'
            ],
            'service_category_ids' => [
                'array' => 'El formato de categorías no es válido.',
                'min' => 'Debes seleccionar al menos una categoría.',
            ],
            'service_category_ids.*' => [
                'integer' => 'Cada categoría debe ser un número entero.',
                'exists' => 'Una o más categorías seleccionadas no existen en el sistema.',
            ],
            'reception_date' => [
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {

            $reception = $this->route('reception');

            if (!$reception) {
                return;
            }

            $clientId = $this->input('client_id', $reception->client_id);
            $vehicleId = $this->input('vehicle_id', $reception->vehicle_id);

            $vehicleBelongsToClient = Vehicle::query()
                ->where('id', $vehicleId)
                ->where('client_id', $clientId)
                ->exists();

            if (!$vehicleBelongsToClient) {
                $validator->errors()->add(
                    'vehicle_id',
                    'El vehículo seleccionado no pertenece al cliente indicado.'
                );
            }

            $hasOpenReception = Reception::query()
                ->where('vehicle_id', $vehicleId)
                ->where('id', '!=', $reception->id)
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
                    'El vehículo ya tiene otra recepción activa.'
                );
            }
        });
    }

}