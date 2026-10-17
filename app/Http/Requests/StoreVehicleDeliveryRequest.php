<?php

namespace App\Http\Requests;

use App\Enums\FuelLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreVehicleDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'final_mileage' => ['required', 'integer', 'min:0'],
            'fuel_level' => ['nullable', Rule::enum(FuelLevel::class)],
            'received_by_name' => ['required', 'string', 'min:3', 'max:150', 'regex:/^[\pL\s.\'-]+$/u'],
            'received_by_document' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'customer_accepted' => ['required', 'accepted'],
            'signature' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'observations' => ['nullable', 'string', 'max:1000', 'regex:/^[\pL\pN\s.,;:()\-#@!?%]*$/u'],
        ];
    }

    public function messages(): array
    {
        return [
            'final_mileage' => [
                'required' => 'El kilometraje de entrega es obligatorio.',
                'integer' => 'El kilometraje de entrega debe ser un número entero.',
                'min' => 'El kilometraje de entrega no puede ser negativo.',
            ],
            'fuel_level' => [
                Enum::class => 'El nivel de combustible no es válido.',
            ],
            'received_by_name' => [
                'required' => 'El nombre de quien retira el vehículo es obligatorio.',
                'min' => 'El nombre de quien retira debe tener al menos 3 caracteres.',
                'max' => 'El nombre de quien retira no debe superar los 150 caracteres.',
                'regex' => 'El nombre de quien retira solo puede contener letras, espacios y . \' -',
            ],
            'received_by_document' => [
                'required' => 'El documento de quien retira el vehículo es obligatorio.',
                'max' => 'El documento no debe superar los 30 caracteres.',
                'regex' => 'El documento solo puede contener letras, números, puntos y guiones.',
            ],
            'customer_accepted' => [
                'required' => 'La conformidad del cliente es obligatoria.',
                'accepted' => 'El cliente debe dar su conformidad para registrar la entrega.',
            ],
            'signature' => [
                'image' => 'La firma debe ser una imagen.',
                'mimes' => 'La firma debe ser jpg, jpeg, png o webp.',
                'max' => 'La firma no debe superar los 2 MB.',
            ],
            'observations' => [
                'max' => 'Las observaciones no deben superar los 1000 caracteres.',
                'regex' => 'Las observaciones contienen caracteres no permitidos.',
            ],
        ];
    }
}
