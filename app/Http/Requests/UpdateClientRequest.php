<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client');

        return [
            'first_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u'
            ],
            'last_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u'
            ],
            'phone' => [
                'required',
                'regex:/^\+?[1-9]\d{7,14}$/'
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:50',
                'lowercase',
                Rule::unique('clients', 'email')->ignore($clientId),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
                'regex:/^[\pL\pN\s.,;:()\-#@!?]*$/u',
            ],

            'vehicles' => [
                'nullable',
                'array'
            ],

            'vehicles.*.id' => [
                'nullable',
                'integer',
                'exists:vehicles,id'
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
                'string',
                'max:20',
                'regex:/^[A-Z0-9-]+$/i'
            ],

            'vehicles.*.color' => [
                'nullable',
                'string',
                'max:30'
            ],

            'vehicles.*.notes' => [
                'nullable',
                'string',
                'max:500'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre es obligatorio.',
            'first_name.regex' => 'El nombre solo puede contener letras y espacios.',

            'last_name.required' => 'El apellido es obligatorio.',
            'last_name.regex' => 'El apellido solo puede contener letras y espacios.',

            'phone.required' => 'El teléfono es obligatorio.',
            'phone.regex' => 'Formato de teléfono inválido.',

            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Formato de correo inválido.',
            'email.unique' => 'Este correo ya está en uso.',

            'vehicles.array' => 'Los vehículos deben ser un arreglo.',

            'vehicles.*.brand_id.required' => 'La marca es obligatoria.',
            'vehicles.*.vehicle_model_id.required' => 'El modelo es obligatorio.',

            'vehicles.*.no_plate.required' => 'Debe indicar si el vehículo tiene chapa.',
            'vehicles.*.no_plate.boolean' => 'El campo no_plate debe ser verdadero o falso.',

            'vehicles.*.plate.regex' => 'La matrícula solo puede contener letras y números.',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->vehicles) {
            $this->merge([
                'vehicles' => collect($this->vehicles)->map(function ($v) {

                    if (!empty($v['plate'])) {
                        $v['plate'] = strtoupper($v['plate']);
                    }

                    if ($v['no_plate'] === true) {
                        $v['plate'] = null;
                    }

                    return $v;
                })->toArray()
            ]);
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $vehicles = collect($this->vehicles ?? []);

            // 🔹 Validar lógica plate vs no_plate
            foreach ($vehicles as $index => $vehicle) {

                $plate = $vehicle['plate'] ?? null;
                $noPlate = $vehicle['no_plate'] ?? false;

                if (!$noPlate && empty($plate)) {
                    $validator->errors()->add(
                        "vehicles.$index.plate",
                        'La matrícula es obligatoria cuando el vehículo tiene chapa.'
                    );
                }

                if ($noPlate && !empty($plate)) {
                    $validator->errors()->add(
                        "vehicles.$index.plate",
                        'No debe ingresar matrícula si el vehículo no tiene chapa.'
                    );
                }
            }

            // 🔹 Validar duplicados en el request
            $plates = $vehicles
                ->pluck('plate')
                ->filter()
                ->map(fn ($p) => strtoupper($p));

            if ($plates->duplicates()->isNotEmpty()) {
                $validator->errors()->add(
                    'vehicles',
                    'No se permiten matrículas duplicadas.'
                );
            }
        });
    }
}