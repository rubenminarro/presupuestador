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
            
            /* user permissions */
            'user.index',
            'user.store',
            'user.show',
            'user.update',
            'user.activate',
            'user.destroy',

            /* permission permissions */
            'permission.index',
            'permission.store',
            'permission.show',
            'permission.update',
            'permission.activate',
            'permission.destroy',

            /* role permissions */
            'role.index',
            'role.store',
            'role.show',
            'role.update',
            'role.activate',
            'role.destroy',
            'role.permissionsGroupedByModule',

            /* client permissions */
            'client.index',
            'client.store',
            'client.show',
            'client.update',
            'client.destroy',

            /* brand permissions */
            'brand.index',
            'brand.store',
            'brand.show',
            'brand.update',
            'brand.destroy',

            /* vehicle model permissions */
            'vehicle_model.index',
            'vehicle_model.store',
            'vehicle_model.show',
            'vehicle_model.update',
            'vehicle_model.destroy',

            /* vehicle permissions */
            'vehicle.index',
            'vehicle.store',
            'vehicle.show',
            'vehicle.update',
            'vehicle.destroy',

            /* checklist permissions */
            'checklist.index',
            'checklist.store',
            'checklist.show',
            'checklist.update',
            'checklist.destroy',

            /* reception permissions */
            'reception.index',
            'reception.store',
            'reception.show',
            'reception.update',
            'reception.destroy',

            /* reception checklist permissions */
            'reception_checklist.show',
            'reception_checklist.update',

            /* reception photo permissions */
            'reception_photo.index',
            'reception_photo.store',
            'reception_photo.update',
            'reception_photo.destroy',

            /* diagnostic permissions */
            'diagnostic.index',
            'diagnostic.store',
            'diagnostic.show',
            'diagnostic.update',
            'diagnostic.destroy',

            /* diagnostic item permissions */
            'diagnostic_item.index',
            'diagnostic_item.store',
            'diagnostic_item.show',
            'diagnostic_item.update',
            'diagnostic_item.destroy',

            /* diagnostic item photo permissions */
            'diagnostic_item_photo.index',
            'diagnostic_item_photo.store',
            'diagnostic_item_photo.update',
            'diagnostic_item_photo.destroy',

            /* budget permissions */
            'budget.index',
            'budget.store',
            'budget.show',
            'budget.update',
            'budget.destroy',
            'budget.send',
            'budget.approve',
            'budget.reject',
            'budget.cancel',
            'budget.reopen',

            /* budget item permissions */
            'budget_item.index',
            'budget_item.store',
            'budget_item.show',
            'budget_item.update',
            'budget_item.destroy',

            /* mechanic permissions */
            'mechanic.index',
            'mechanic.store',
            'mechanic.show',
            'mechanic.update',
            'mechanic.destroy',
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $admin->syncPermissions(
            Permission::where('guard_name', 'api')->get()
        );
        
        $mecanico->syncPermissions([]);

    }
}
