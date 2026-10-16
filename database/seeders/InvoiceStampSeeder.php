<?php

namespace Database\Seeders;

use App\Models\InvoiceStamp;
use Illuminate\Database\Seeder;

/**
 * Timbrado de ejemplo (001-001), vigente por un año desde hoy.
 */
class InvoiceStampSeeder extends Seeder
{
    public function run(): void
    {
        InvoiceStamp::firstOrCreate(
            ['stamp_number' => '12345678', 'establishment' => '001', 'expedition_point' => '001'],
            [
                'valid_from' => now()->startOfDay(),
                'valid_until' => now()->addYear()->startOfDay(),
                'next_number' => 1,
                'is_active' => true,
            ]
        );
    }
}
