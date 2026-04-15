<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $adminRole = Role::firstOrCreate([
            'name' => 'administrador',
            'guard_name' => 'api'
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@mail.com.py'],
            [
                'name' => 'administrador',
                'first_name' => 'Ruben',
                'last_name' => 'Minarro',
                'password' => Hash::make(env('ADMIN_PASSWORD'))
            ]
        );

        if (!$admin->hasRole('administrador')) {
            $admin->assignRole($adminRole);
        }
    }
}
