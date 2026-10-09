<?php

namespace Database\Seeders;

use App\Enums\InventoryMovementType;
use App\Models\Part;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(InventoryService $inventoryService): void
    {
        $user = User::where('email', 'admin@mail.com.py')->first();

        if (!$user) {
            return;
        }

        // El refrigerante queda por debajo del mínimo para probar el filtro below_minimum.
        $initialStock = [
            'FIL-ACE-001' => 12,
            'FIL-AIR-001' => 8,
            'FRE-PAS-001' => 4,
            'LUB-5W30-001' => 60,
            'REF-REF-001' => 3.5,
        ];

        foreach ($initialStock as $sku => $quantity) {
            $part = Part::where('sku', $sku)->first();

            if (!$part || $part->movements()->exists()) {
                continue;
            }

            $inventoryService->registerEntry([
                'part_id' => $part->id,
                'type' => InventoryMovementType::INITIAL->value,
                'quantity' => $quantity,
                'unit_cost' => $part->cost_price,
                'reason' => 'Carga inicial de datos de ejemplo.',
            ], $user);
        }
    }
}
