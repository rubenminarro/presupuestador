<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            MechanicSeeder::class,
            BrandSeeder::class,
            VehicleModelSeeder::class,
            ServiceCategorySeeder::class,
            CheckListItemSeeder::class,
            CheckListItemServiceCategorySeeder::class,
            ClientsSeeder::class,
            VehiclesSeeder::class,
            ReceptionSeeder::class,
            DiagnosticSeeder::class,
            DiagnosticItemSeeder::class,
            DiagnosticItemPhotoSeeder::class,
        ]);
    }
}
