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
        
        $roles = ['administrador', 'mecanico'];
        $createdRoles = [];

        foreach ($roles as $roleName) {
            $createdRoles[$roleName] = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'api'
            ]);
        }

        $usersData = [
            [
                'email' => 'admin@mail.com.py',
                'name' => 'administrador',
                'first_name' => 'Ruben',
                'last_name' => 'Minarro',
                'password' => Hash::make(env('ADMIN_PASSWORD')),
                'role' => 'administrador'
            ],
            [
                'email' => 'mecanico1@mail.com.py',
                'name' => 'mecanico1',
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'password' => Hash::make(env('ADMIN_PASSWORD')),
                'role' => 'mecanico'
            ],
            [
                'email' => 'mecanico2@mail.com.py',
                'name' => 'mecanico2',
                'first_name' => 'Carlos',
                'last_name' => 'Gómez',
                'password' => Hash::make(env('ADMIN_PASSWORD')),
                'role' => 'mecanico'
            ],
        ];

        foreach ($usersData as $data) {
            
            $roleName = $data['role'];
            
            unset($data['role']);

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                $data
            );

            if (!$user->hasRole($roleName)) {
                $user->assignRole($createdRoles[$roleName]);
            }
        }
    }
}
