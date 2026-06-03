<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Vehicle;

class VehiclesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [1, 1, 1, '7H4G92', 'AAA111', 'white'],
            [2, 2, 2, '2J8F12', 'BBB222', 'gray'],
            [3, 3, 3, '9K3L45', 'CCC333', 'black'],
            [4, 4, 4, '1M2N56', 'DDD444', 'red'],
            [5, 5, 5, '5P6Q78', 'EEE555', 'blue'],
            [1, 9, 9, '3S4T90', 'FFF666', 'silver'],
            [2, 10, 10, '8V7W12', 'GGG777', 'green'],
            [3, 11, 11, '4X5Y34', 'HHH888', 'burgundy'],
            [4, 13, 13, '6Z1A56', 'III999', 'white'],
            [5, 20, 20, '2B3C78', 'JJJ000', 'gray'],
            [1, 21, 21, '9D4E90', 'KKK123', 'blue'],
            [2, 22, 22, '7F5G12', 'LLL456', 'black'],
            [3, 12, 12, '1H6I34', 'MMM789', 'champagne'],
            [4, 18, 18, '5J7K56', 'NNN012', 'white'],
            [5, 16, 16, '3L8M78', 'PPP345', 'black'],
        ];

        foreach ($data as $vehicleData) {
            Vehicle::create([
                'client_id' => $vehicleData[0],
                'brand_id' => $vehicleData[1],
                'vehicle_model_id' => $vehicleData[2],
                'chassis' => $vehicleData[3],
                'plate' => $vehicleData[4],
                'color' => $vehicleData[5]
            ]);
        }
    }
}