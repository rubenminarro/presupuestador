<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\FuelLevel;
use App\Enums\Status;

class UpdateReceptionRequest extends FormRequest
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
            'client_id' => [
                'sometimes',
                'integer',
                Rule::exists('clients', 'id')->where('active', true),
            ],
            'vehicle_id' => [
                'sometimes',
                'integer',
                Rule::exists('vehicles', 'id')->where('active', true),
            ],
            'reception_date' => [
                'sometimes',
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
                'sometimes',
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
                'sometimes',
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
                'integer' => 'El ID del cliente debe ser un número entero.',
                'exists' => 'El cliente seleccionado no existe.'
            ],
            'vehicle_id' => [
                'integer' => 'El ID del vehículo debe ser un número entero.',
                'exists' => 'El vehículo seleccionado no existe.'
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
            'problem_description' => [
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
}
