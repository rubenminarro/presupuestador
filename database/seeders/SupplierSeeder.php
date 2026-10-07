<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Distribuidora de Repuestos Central',
                'tax_id' => '80000001-1',
                'contact_name' => 'Departamento de ventas',
                'phone' => '021 000 001',
                'email' => 'ventas@repuestoscentral.test',
            ],
            [
                'name' => 'Lubricantes del Este',
                'tax_id' => '80000002-2',
                'contact_name' => 'Atención comercial',
                'phone' => '021 000 002',
                'email' => 'contacto@lubricanteseste.test',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(
                ['tax_id' => $supplier['tax_id']],
                [...$supplier, 'is_active' => true]
            );
        }
    }
}
