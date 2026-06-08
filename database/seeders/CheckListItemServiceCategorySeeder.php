<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CheckListItem;
use App\Models\ServiceCategory;

class CheckListItemServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $general = ServiceCategory::where('code', 'general')->firstOrFail();
        $mechanical = ServiceCategory::where('code', 'mechanical')->firstOrFail();
        $bodywork = ServiceCategory::where('code', 'bodywork')->firstOrFail();
        $electrical = ServiceCategory::where('code', 'electrical')->firstOrFail();
        $tires = ServiceCategory::where('code', 'tires')->firstOrFail();

        $relations = [

            // GENERAL
            'Cédula verde / documento del vehículo' => [$general],
            'Seguro vigente' => [$general],
            'Nivel de combustible' => [$general],
            'Estado interior general' => [$general],
            'Observaciones generales' => [$general],

            // CHAPERÍA
            'Rayones o daños visibles en carrocería' => [$bodywork],
            'Estado general de pintura' => [$bodywork],

            // MECÁNICA
            'Motor enciende correctamente' => [$mechanical],
            'Ruidos extraños en motor' => [$mechanical],
            'Estado de batería' => [$mechanical],
            'Aire acondicionado funciona' => [$mechanical],
            'Frenos responden correctamente' => [$mechanical],

            // NEUMÁTICOS
            'Estado de neumáticos' => [$tires],
            'Rueda de auxilio disponible' => [$tires],

            // EJEMPLO DE ITEM COMPARTIDO
            // 'Tomar fotografías' => [$general, $bodywork],
        ];

        foreach ($relations as $itemName => $categories) {

            $item = CheckListItem::where(
                'name',
                $itemName
            )->first();

            if (!$item) {
                continue;
            }

            $item->serviceCategories()->syncWithoutDetaching(
                collect($categories)->pluck('id')->toArray()
            );
        }
    }
}
