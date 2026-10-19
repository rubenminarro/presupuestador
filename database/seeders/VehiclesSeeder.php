<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\VehicleModel;

class VehiclesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // [documento del cliente, marca, modelo, chasis, placa, color]
        $data = [
            ['12345678', 'Toyota', 'Hilux', '7H4G92', 'AAA111', 'white'],
            ['23456789', 'Hyundai', 'HB20S', '2J8F12', 'BBB222', 'gray'],
            ['34567890', 'Kia', 'Rio', '9K3L45', 'CCC333', 'black'],
            ['45678901', 'Chevrolet', 'Tracker', '1M2N56', 'DDD444', 'red'],
            ['56789012', 'Volkswagen', 'Nivus', '5P6Q78', 'EEE555', 'blue'],
            ['12345678', 'Nissan', 'Leaf', '3S4T90', 'FFF666', 'silver'],
            ['23456789', 'Mitsubishi', 'Pajero Full', '8V7W12', 'GGG777', 'green'],
            ['34567890', 'Ford', 'Ranger', '4X5Y34', 'HHH888', 'burgundy'],
            ['45678901', 'BMW', 'X1', '6Z1A56', 'III999', 'white'],
            ['56789012', 'Honda', 'Fit', '2B3C78', 'JJJ000', 'gray'],
            ['12345678', 'Suzuki', 'Jimny', '9D4E90', 'KKK123', 'blue'],
            ['23456789', 'Mazda', 'CX-30', '7F5G12', 'LLL456', 'black'],
            ['34567890', 'Mercedes-Benz', 'E-Class', '1H6I34', 'MMM789', 'champagne'],
            ['45678901', 'Audi', 'e-tron', '5J7K56', 'NNN012', 'white'],
            ['56789012', 'Jeep', 'Gladiator', '3L8M78', 'PPP345', 'black'],
        ];

        // Idempotente: omite los vehículos cuya placa ya existe.
        foreach ($data as [$document, $brandName, $modelName, $chassis, $plate, $color]) {
            $clientId = Client::where('document_number', $document)->value('id');
            $brandId = Brand::where('name', $brandName)->value('id');
            $modelId = VehicleModel::where('brand_id', $brandId)->where('name', $modelName)->value('id');

            if (!$clientId || !$brandId || !$modelId) {
                continue;
            }

            Vehicle::firstOrCreate(
                ['plate' => $plate],
                [
                    'client_id' => $clientId,
                    'brand_id' => $brandId,
                    'vehicle_model_id' => $modelId,
                    'chassis' => $chassis,
                    'color' => $color,
                ]
            );
        }
    }
}
