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
            'reception-check-lists.show',
            'reception-check-lists.update',

            /* reception photo permissions */
            'receptions-photos.index',
            'receptions-photos.store',
            'receptions-photos.update',
            'receptions-photos.destroy',

            /* diagnostic permissions */
            'diagnostics.index',
            'diagnostics.store',
            'diagnostics.show',
            'diagnostics.update',
            'diagnostics.destroy',

            /* diagnostic item permissions */
            'diagnostic-items.index',
            'diagnostic-items.store',
            'diagnostic-items.show',
            'diagnostic-items.update',
            'diagnostic-items.destroy',

            /* diagnostic item photo permissions */
            'diagnostic-item-photos.index',
            'diagnostic-item-photos.store',
            'diagnostic-item-photos.update',
            'diagnostic-item-photos.destroy',

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
            'budget-items.index',
            'budget-items.store',
            'budget-items.show',
            'budget-items.update',
            'budget-items.destroy',

            /* mechanic permissions */
            'mechanics.index',
            'mechanics.store',
            'mechanics.show',
            'mechanics.update',
            'mechanics.destroy',

            /* work order permissions */
            'work-orders.index',
            'work-orders.store',
            'work-orders.show',
            'work-orders.update',
            'work-orders.start',
            'work-orders.pause',
            'work-orders.resume',
            'work-orders.complete',
            'work-orders.cancel',

            /* work order item permissions */
            'work-order-items.start',
            'work-order-items.complete',
            'work-order-items.cancel',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $admin->syncPermissions(
            Permission::where('guard_name', 'api')->get()
        );

        $mechanicPermissions = [
            'vehicles.index', 
            'vehicles.show',
            
            'receptions.index', 
            'receptions.show', 
            'receptions.update',
            
            'reception-check-lists.show', 
            'reception-check-lists.update',
            
            'receptions-photos.index', 
            'receptions-photos.store', 
            'receptions-photos.destroy',

            'diagnostics.index', 
            'diagnostics.store', 
            'diagnostics.show', 
            'diagnostics.update',
            
            'diagnostic-items.index', 
            'diagnostic-items.store', 
            'diagnostic-items.show', 
            'diagnostic-items.update', 
            'diagnostic-items.destroy',

            'diagnostic-item-photos.index', 
            'diagnostic-item-photos.store', 
            'diagnostic-item-photos.update', 
            'diagnostic-item-photos.destroy',

            'work-orders.index', 
            'work-orders.show', 
            
            'work-orders.start', 
            'work-orders.pause', 
            'work-orders.resume', 
            'work-orders.complete',
            
            'work-order-items.start', 
            'work-order-items.complete',

        ];

        $mecanico->syncPermissions(
            Permission::where('guard_name', 'api')->whereIn('name', $mechanicPermissions)->get()
        );
    }
}