<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder  extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = Role::firstOrCreate([
            'name' => 'administrador',
            'guard_name' => 'api'
        ]);
        
        $permissions = [
            'user.index',
            'user.store',
            'user.show',
            'user.update',
            'user.activate',
            'permission.index',
            'permission.store',
            'permission.show',
            'permission.update',
            'permission.activate',
            'permission.destroy',
            'role.index',
            'role.store',
            'role.show',
            'role.update',
            'role.activate',
            'role.destroy'
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $admin->givePermissionTo(Permission::all());

    }
}
