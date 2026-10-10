<?php

namespace App\Services;

use App\Models\Vehicle;

class VehicleService
{
    /**
     * Alta de vehículo, compartida por /vehicles y /clients/{client}/vehicles.
     */
    public function create(array $data): Vehicle
    {
        $vehicle = Vehicle::create($data);

        return $vehicle->load([
            'client',
            'brand',
            'vehicleModel',
        ]);
    }
}
