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
            |------------------------------------------------------------------
            | IDENTIFICACIÓN Y LEGAL (CRÍTICOS)
            |------------------------------------------------------------------
            */
            ['name' => 'Cédula verde / documento del vehículo', 'type' => 'boolean', 'required' => true],
            ['name' => 'Seguro vigente', 'type' => 'boolean', 'required' => true],

            /*
            |------------------------------------------------------------------
            | ESTADO GENERAL EXTERIOR
            |------------------------------------------------------------------
            */
            ['name' => 'Rayones o daños visibles en carrocería', 'type' => 'boolean', 'required' => true],
            ['name' => 'Estado general de pintura', 'type' => 'text', 'required' => false],

            /*
            |------------------------------------------------------------------
            | MECÁNICA BÁSICA
            |------------------------------------------------------------------
            */
            ['name' => 'Nivel de combustible', 'type' => 'number', 'required' => true],
            ['name' => 'Motor enciende correctamente', 'type' => 'boolean', 'required' => true],
            ['name' => 'Ruidos extraños en motor', 'type' => 'boolean', 'required' => false],

            /*
            |------------------------------------------------------------------
            | NEUMÁTICOS
            |------------------------------------------------------------------
            */
            ['name' => 'Estado de neumáticos', 'type' => 'text', 'required' => true],
            ['name' => 'Rueda de auxilio disponible', 'type' => 'boolean', 'required' => false],

            /*
            |------------------------------------------------------------------
            | SISTEMA ELÉCTRICO
            |------------------------------------------------------------------
            */
            ['name' => 'Luces principales funcionan', 'type' => 'boolean', 'required' => true],
            ['name' => 'Estado de batería', 'type' => 'boolean', 'required' => false],

            /*
            |------------------------------------------------------------------
            | INTERIOR
            |------------------------------------------------------------------
            */
            ['name' => 'Estado interior general', 'type' => 'text', 'required' => false],
            ['name' => 'Aire acondicionado funciona', 'type' => 'boolean', 'required' => false],

            /*
            |------------------------------------------------------------------
            | SEGURIDAD
            |------------------------------------------------------------------
            */
            ['name' => 'Frenos responden correctamente', 'type' => 'boolean', 'required' => true],

            /*
            |------------------------------------------------------------------
            | OBSERVACIONES
            |------------------------------------------------------------------
            */
            ['name' => 'Observaciones generales', 'type' => 'text', 'required' => false],
        ];

        foreach ($items as $item) {
            CheckListItem::updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}