<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'min:2',
                'max:100',
                'string', 
                'regex:/^[\pL\pN\s-]+$/u',
                Rule::unique('vehicle_models', 'name'),
            ],
            'brand_id' => [
                'required',
                'integer',
                Rule::exists('brands', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name' => [
                'required' => 'El nombre del modelo de vehículo es obligatorio.',
                'string'   => 'El nombre del modelo de vehículo debe tener el formato correcto.',
                'min'      => 'El nombre del modelo de vehículo debe tener al menos 2 caracteres.',
                'max'      => 'El nombre del modelo de vehículo no debe tener más de 100 caracteres.',
                'regex'    => 'El nombre del modelo de vehículo solo puede contener letras, números y guiones.',
                'unique'   => 'Este modelo de vehículo ya existe en el sistema.',
            ],
            'brand_id' => [
                'required' => 'El ID de la marca es obligatorio.',
                'integer'  => 'El ID de la marca debe ser un número entero.',
                'exists'   => 'La marca seleccionada no existe.',
            ],
        ];
    }
}
