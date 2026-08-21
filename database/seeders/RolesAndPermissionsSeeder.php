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

        $mecanico = Role::firstOrCreate([
            'name' => 'mechanic',
            'guard_name' => 'api',
        ]);
        
        $permissions = [
            
            /*user permissions*/
            'user.index',
            'user.store',
            'user.show',
            'user.update',
            'user.activate',
            'user.destroy',
            
            /*role and permission permissions*/
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
            'role.destroy',

            /*budget permissions*/
            'budget.view',
            'budget.create',
            'budget.update',
            'budget.delete',
            'budget.send',
            'budget.approve',
            'budget.reject',
            'budget.cancel',
            'budget.reopen',

            /*budget item permissions*/
            'budget_item.view',
            'budget_item.create',
            'budget_item.update',
            'budget_item.delete',
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $admin->givePermissionTo(Permission::all());
        $mecanico->syncPermissions([]);

    }
}
