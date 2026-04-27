<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\VehicleModel;

class VehicleModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modelsByBrand = [
            1  => ['Hilux', 'Corolla Cross', 'Corolla', 'Etios', 'Yaris', 'RAV4', 'SW4', 'Land Cruiser Prado', 'Land Cruiser 300', 'Tacoma'], // Toyota
            2  => ['HB20', 'HB20S', 'Creta', 'Tucson', 'Santa Fe', 'Palisade', 'Kona Electric', 'Ioniq 5', 'Venue', 'Accent'], // Hyundai
            3  => ['Picanto', 'Soluto', 'Rio', 'Seltos', 'Sportage', 'Sorento', 'Sonet', 'Carens', 'EV6', 'Cerato'], // Kia
            4  => ['S10', 'Onix', 'Onix Plus', 'Tracker', 'Captiva', 'Montana', 'Silverado', 'Cruze', 'Spin', 'Equinox'], // Chevrolet
            5  => ['Gol', 'Polo', 'Virtus', 'T-Cross', 'Nivus', 'Taos', 'Tiguan Allspace', 'Amarok', 'Saveiro', 'ID.4'], // Volkswagen
            6  => ['Yuan Plus', 'Song Plus DM-i', 'Dolphin', 'Seal', 'Han EV', 'Tang EV', 'Yuan Pro', 'D1', 'Seagull', 'Qin Plus'], // BYD
            7  => ['CS15', 'CS35 Plus', 'CS55 Plus', 'UNI-T', 'UNI-K', 'Alsvin', 'Hunter', 'X7 Plus', 'Eado Plus', 'Lumin'], // Changan
            8  => ['D-Max', 'Mu-X', 'NLR', 'NMR', 'NPR', 'NQR', 'FSR', 'FVR', 'FVZ', 'CYZ'], // Isuzu
            9  => ['Kicks', 'Frontier', 'Versa', 'Sentra', 'X-Trail', 'Qashqai', 'Patrol', 'Urvan', 'Leaf', 'Pathfinder'], // Nissan
            10 => ['L200 Triton', 'Montero Sport', 'Outlander', 'ASX', 'Eclipse Cross', 'XPANDER', 'Mirage', 'Attrage', 'Lancer', 'Pajero Full'], // Mitsubishi
            11 => ['Ranger', 'F-150', 'Maverick', 'Everest', 'Territory', 'Bronco Sport', 'Mustang', 'Explorer', 'Transit', 'Edge'], // Ford
            12 => ['C-Class', 'E-Class', 'S-Class', 'GLA', 'GLB', 'GLC', 'GLE', 'GLS', 'EQE', 'EQS'], // Mercedes-Benz
            13 => ['3 Series', '5 Series', 'X1', 'X3', 'X5', 'X6', 'X7', 'iX', 'i4', 'M3'], // BMW
            14 => ['Haval H6', 'Haval Jolion', 'Poer', 'Wingle 7', 'Tank 300', 'Tank 500', 'Ora 03', 'Haval M6', 'Haval Dargo', 'Wingle 5'], // GWM
            15 => ['Tiggo 2 Pro', 'Tiggo 4 Pro', 'Tiggo 7 Pro', 'Tiggo 8 Pro', 'Arrizo 5', 'Arrizo 6', 'Omoda 5', 'EQ1', 'Tiggo 3', 'Tiggo 5x'], // Chery
            16 => ['Renegade', 'Compass', 'Commander', 'Grand Cherokee', 'Wrangler', 'Gladiator', 'Avenger', 'Meridian', 'Cherokee', 'Rubicon'], // Jeep
            17 => ['T6', 'T8', 'JS2', 'JS3', 'JS4', 'JS6', 'JS8', 'E10X', 'Sunray', 'M4'], // JAC
            18 => ['A3', 'A4', 'A6', 'Q3', 'Q5', 'Q7', 'Q8', 'e-tron', 'RS e-tron GT', 'Q2'], // Audi
            19 => ['Strada', 'Toro', 'Cronos', 'Pulse', 'Fastback', 'Mobi', 'Argo', 'Fiorino', 'Ducato', '500e'], // Fiat
            20 => ['Civic', 'HR-V', 'CR-V', 'City', 'City Hatchback', 'WR-V', 'Accord', 'Pilot', 'ZR-V', 'Fit'], // Honda
            21 => ['Jimny', 'Swift', 'Vitara', 'Grand Vitara', 'S-Presso', 'Ertiga', 'Baleno', 'Dzire', 'Celerio', 'Fronx'], // Suzuki
            22 => ['CX-5', 'CX-30', 'CX-3', 'CX-50', 'CX-60', 'CX-90', 'Mazda 2', 'Mazda 3', 'BT-50', 'MX-5'], // Mazda
            23 => ['Coolray', 'Azkarra', 'Okavango', 'Emgrand', 'GX3 Pro', 'Tugella', 'Monjaro', 'Geometry C', 'Starray', 'Binray'], // Geely
            24 => ['Defender', 'Range Rover Evoque', 'Range Rover Sport', 'Range Rover Velar', 'Discovery', 'Discovery Sport', 'Range Rover', 'Defender 110', 'Defender 90', 'Freelander'], // Land Rover
            25 => ['Kwid', 'Stepway', 'Duster', 'Oroch', 'Kardian', 'Logan', 'Sandero', 'Captur', 'Megane E-Tech', 'Master'], // Renault
        ];

        foreach ($modelsByBrand as $brandId => $models) {
            foreach ($models as $modelName) {
                VehicleModel::create([
                    'brand_id' => $brandId,
                    'name'     => $modelName,
                ]);
            }
        }
    }
}
