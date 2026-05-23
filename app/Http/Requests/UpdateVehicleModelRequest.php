<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        $idVehicleModel = $this->route('vehicleModel') ? $this->route('vehicleModel')->id : null;

        return [
            'name' => [
                'sometimes',
                'min:2',
                'max:100',
                'string', 
                'regex:/^[\pL\pN\s-]+$/u',
                Rule::unique('vehicle_models', 'name')->ignore($idVehicleModel),
            ],
            'brand_id' => [
                'sometimes',
                'integer',
                Rule::exists('brands', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name' => [
                'string'   => 'El nombre del modelo de vehículo debe tener el formato correcto.',
                'min'      => 'El nombre del modelo de vehículo debe tener al menos 2 caracteres.',
                'max'      => 'El nombre del modelo de vehículo no debe tener más de 100 caracteres.',
                'regex'    => 'El nombre del modelo de vehículo solo puede contener letras, números y guiones.',
                'unique'   => 'Este modelo de vehículo ya existe en el sistema.',
            ],
            'brand_id' => [
                'integer'  => 'El ID de la marca debe ser un número entero.',
                'exists'   => 'La marca seleccionada no existe.',
            ],
        ];
    }
}
