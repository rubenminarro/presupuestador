<?php

namespace App\Http\Requests;

/**
 * Alta de vehículo desde la ruta anidada /clients/{client}/vehicles:
 * el propietario se toma de la URL y prevalece sobre el body.
 */
class StoreClientVehicleRequest extends StoreVehicleRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'client_id' => $this->route('client')->id,
        ]);
    }
}
