<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
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
            
            /* users permissions */
            'users.index',
            'users.store',
            'users.show',
            'users.update',
            'users.destroy',

            /* permissions permissions */
            'permissions.index',
            'permissions.store',
            'permissions.show',
            'permissions.update',
            'permissions.destroy',

            /* roles permissions */
            'roles.index',
            'roles.store',
            'roles.show',
            'roles.update',
            'roles.destroy',
            'roles.permissions-grouped-by-module',

            /* clients permissions */
            'clients.index',
            'clients.store',
            'clients.show',
            'clients.update',
            'clients.destroy',

            /* brand permissions */
            'brands.index',
            'brands.store',
            'brands.show',
            'brands.update',
            'brands.destroy',

            /* vehicle model permissions */
            'vehicle_models.index',
            'vehicle_models.store',
            'vehicle_models.show',
            'vehicle_models.update',
            'vehicle_models.destroy',

            /* vehicle permissions */
            'vehicles.index',
            'vehicles.store',
            'vehicles.show',
            'vehicles.update',
            'vehicles.destroy',

            /* checklist permissions */
            'checklists.index',
            'checklists.store',
            'checklists.show',
            'checklists.update',
            'checklists.destroy',

            /* reception permissions */
            'receptions.index',
            'receptions.store',
            'receptions.show',
            'receptions.update',
            'receptions.destroy',

            /* reception checklist permissions */
            'reception_checklists.show',
            'reception_checklists.update',

            /* reception photo permissions */
            'reception_photos.index',
            'reception_photos.store',
            'reception_photos.update',
            'reception_photos.destroy',

            /* diagnostic permissions */
            'diagnostics.index',
            'diagnostics.store',
            'diagnostics.show',
            'diagnostics.update',
            'diagnostics.destroy',

            /* diagnostic item permissions */
            'diagnostic_items.index',
            'diagnostic_items.store',
            'diagnostic_items.show',
            'diagnostic_items.update',
            'diagnostic_items.destroy',

            /* diagnostic item photo permissions */
            'diagnostic_item_photo.index',
            'diagnostic_item_photo.store',
            'diagnostic_item_photo.update',
            'diagnostic_item_photo.destroy',

            /* budget permissions */
            'budgets.index',
            'budgets.store',
            'budgets.show',
            'budgets.update',
            'budgets.destroy',
            'budgets.send',
            'budgets.approve',
            'budgets.reject',
            'budgets.cancel',
            'budgets.reopen',

            /* budget item permissions */
            'budget_items.index',
            'budget_items.store',
            'budget_items.show',
            'budget_items.update',
            'budget_items.destroy',

            /* mechanic permissions */
            'mechanics.index',
            'mechanics.store',
            'mechanics.show',
            'mechanics.update',
            'mechanics.destroy',

            /* work order permissions */
            'work_orders.index',
            'work_orders.store',
            'work_orders.show',
            'work_orders.update',
            'work_orders.start',
            'work_orders.pause',
            'work_orders.resume',
            'work_orders.complete',
            'work_orders.cancel',

            /* work order item permissions */
            'work_order_items.start',
            'work_order_items.complete',
            'work_order_items.cancel',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // Asignar absolutamente todos los permisos al administrador
        $admin->syncPermissions(
            Permission::where('guard_name', 'api')->get()
        );

        // Permisos operativos asignados al mecánico
        $mechanicPermissions = [
            'vehicles.index', 
            'vehicles.show',
            
            'receptions.index', 
            'receptions.show', 
            'receptions.update',
            
            'reception_checklists.show', 
            'reception_checklists.update',
            
            'reception_photos.index', 
            'reception_photos.store', 
            'reception_photos.destroy',

            'diagnostics.index', 
            'diagnostics.store', 
            'diagnostics.show', 
            'diagnostics.update',
            
            'diagnostic_items.index', 
            'diagnostic_items.store', 
            'diagnostic_items.show', 
            'diagnostic_items.update', 
            'diagnostic_items.destroy',

            'diagnostic_item_photos.index', 
            'diagnostic_item_photos.store', 
            'diagnostic_item_photos.update', 
            'diagnostic_item_photos.destroy',

            'work_orders.index', 
            'work_orders.show', 
            'work_orders.start', 
            'work_orders.pause', 
            'work_orders.resume', 
            'work_orders.complete',
            
            'work_order_items.start', 
            'work_order_items.complete',

        ];

        $mecanico->syncPermissions(
            Permission::where('guard_name', 'api')->whereIn('name', $mechanicPermissions)->get()
        );
    }
}