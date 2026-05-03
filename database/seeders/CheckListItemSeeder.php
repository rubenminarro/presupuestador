<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CheckListItem;

class CheckListItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [

            /*
            |--------------------------------------------------------------------------
            | CARROCERÍA Y ESTÉTICA
            |--------------------------------------------------------------------------
            */
            ['name' => 'Rayones visibles en chapería', 'type' => 'boolean'],
            ['name' => 'Abolladuras en paragolpes delantero', 'type' => 'boolean'],
            ['name' => 'Abolladuras en paragolpes trasero', 'type' => 'boolean'],
            ['name' => 'Estado de pintura general', 'type' => 'text'],
            ['name' => 'Estado de llantas (golpes o deformaciones)', 'type' => 'boolean'],
            ['name' => 'Estado de espejos retrovisores', 'type' => 'boolean'],

            /*
            |--------------------------------------------------------------------------
            | MECÁNICA Y FLUIDOS
            |--------------------------------------------------------------------------
            */
            ['name' => 'Nivel de combustible', 'type' => 'number'],
            ['name' => 'Nivel de aceite motor', 'type' => 'boolean'],
            ['name' => 'Nivel de líquido refrigerante', 'type' => 'boolean'],
            ['name' => 'Nivel de líquido de frenos', 'type' => 'boolean'],
            ['name' => 'Funcionamiento del motor en frío', 'type' => 'boolean'],
            ['name' => 'Temperatura del motor estable', 'type' => 'boolean'],
            ['name' => 'Estado de batería (arranque)', 'type' => 'boolean'],
            ['name' => 'Estado de correas visibles', 'type' => 'boolean'],

            /*
            |--------------------------------------------------------------------------
            | NEUMÁTICOS Y SUSPENSIÓN
            |--------------------------------------------------------------------------
            */
            ['name' => 'Presión de neumáticos', 'type' => 'number'],
            ['name' => 'Desgaste de neumáticos', 'type' => 'text'],
            ['name' => 'Estado de rueda de auxilio', 'type' => 'boolean'],
            ['name' => 'Estado de suspensión (ruidos o golpes)', 'type' => 'boolean'],

            /*
            |--------------------------------------------------------------------------
            | SISTEMA ELÉCTRICO Y LUCES
            |--------------------------------------------------------------------------
            */
            ['name' => 'Luces bajas', 'type' => 'boolean'],
            ['name' => 'Luces altas', 'type' => 'boolean'],
            ['name' => 'Luces de freno', 'type' => 'boolean'],
            ['name' => 'Señaleros', 'type' => 'boolean'],
            ['name' => 'Balizas', 'type' => 'boolean'],
            ['name' => 'Funcionamiento de tablero', 'type' => 'boolean'],
            ['name' => 'Estado de batería terminales', 'type' => 'boolean'],

            /*
            |--------------------------------------------------------------------------
            | INTERIOR DEL VEHÍCULO
            |--------------------------------------------------------------------------
            */
            ['name' => 'Estado de asientos', 'type' => 'text'],
            ['name' => 'Limpieza interior', 'type' => 'boolean'],
            ['name' => 'Funcionamiento de aire acondicionado', 'type' => 'boolean'],
            ['name' => 'Funcionamiento de calefacción', 'type' => 'boolean'],
            ['name' => 'Estado de cinturones de seguridad', 'type' => 'boolean'],

            /*
            |--------------------------------------------------------------------------
            | DOCUMENTACIÓN Y SEGURIDAD
            |--------------------------------------------------------------------------
            */
            ['name' => 'Cédula verde / título', 'type' => 'boolean'],
            ['name' => 'Habilitación municipal vigente', 'type' => 'boolean'],
            ['name' => 'Seguro vigente', 'type' => 'boolean'],
            ['name' => 'Extintor vigente', 'type' => 'boolean'],
            ['name' => 'Botiquín de primeros auxilios', 'type' => 'boolean'],
            ['name' => 'Balizas reglamentarias', 'type' => 'boolean'],

            /*
            |--------------------------------------------------------------------------
            | OBSERVACIONES
            |--------------------------------------------------------------------------
            */
            ['name' => 'Observaciones generales del vehículo', 'type' => 'text'],
            ['name' => 'Daños previos reportados por el cliente', 'type' => 'text'],
        ];

        foreach ($items as $item) {
            CheckListItem::updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}