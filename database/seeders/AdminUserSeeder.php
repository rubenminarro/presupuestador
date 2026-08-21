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
        
        $roles = ['administrador', 'mechanic'];
        $createdRoles = [];

        foreach ($roles as $roleName) {
            $createdRoles[$roleName] = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'api'
            ]);
        }

        $usersData = [
            [
                'email' => 'rminarro@mail.com.py',
                'name' => 'rminarro',
                'first_name' => 'Ruben',
                'last_name' => 'Minarro',
                'password' => Hash::make(env('ADMIN_PASSWORD')),
                'role' => 'administrador'
            ],
            [
                'email' => 'jperez@mail.com.py',
                'name' => 'jperez',
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'password' => Hash::make(env('ADMIN_PASSWORD')),
                'role' => 'mechanic'
            ],
            [
                'email' => 'cgomez@mail.com.py',
                'name' => 'cgomez',
                'first_name' => 'Carlos',
                'last_name' => 'Gómez',
                'password' => Hash::make(env('ADMIN_PASSWORD')),
                'role' => 'mechanic'
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
