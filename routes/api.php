<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserRoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\VehicleModelController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\ReceptionController;
use App\Http\Controllers\Api\CheckListController;
use App\Http\Controllers\Api\ReceptionCheckListController;  

Route::middleware(['auth:sanctum', 'role:administrador'])->group(function () {
    
    Route::get('/users', [UserRoleController::class, 'index']);
    Route::post('/users', [UserRoleController::class, 'store']);
    Route::get('/user/{user}', [UserRoleController::class, 'show']);
    Route::patch('/user/{user}', [UserRoleController::class, 'update']);
    Route::post('/user/activate/{user}', [UserRoleController::class, 'activate']);

    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::post('/permissions', [PermissionController::class, 'store']);
    Route::get('/permission/{permission}', [PermissionController::class, 'show']);
    Route::patch('/permission/{permission}', [PermissionController::class, 'update']);
    Route::post('/permission/activate/{permission}', [PermissionController::class, 'activate']);
    Route::delete('/permission/{permission}', [PermissionController::class, 'destroy']);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/role/permissions', [RoleController::class, 'permissions']);
    Route::get('/role/{role}', [RoleController::class, 'show']);
    Route::patch('/role/{role}', [RoleController::class, 'update']);
    Route::post('/role/activate/{role}', [RoleController::class, 'activate']);
    Route::delete('/role/{role}', [RoleController::class, 'destroy']);
    
    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/client/{client}', [ClientController::class, 'show']);
    Route::patch('/client/{client}', [ClientController::class, 'update']);
    Route::post('/client/activate/{client}', [ClientController::class, 'activate']);

    Route::get('/brands', [BrandController::class, 'index']);
    Route::post('/brands', [BrandController::class, 'store']);
    Route::get('/brand/{brand}', [BrandController::class, 'show']);
    Route::patch('/brand/{brand}', [BrandController::class, 'update']);
    Route::post('/brand/activate/{brand}', [BrandController::class, 'activate']);

    Route::get('/vehicle-models', [VehicleModelController::class, 'index']);
    Route::post('/vehicle-models', [VehicleModelController::class, 'store']);
    Route::get('/vehicle-model/{vehicleModel}', [VehicleModelController::class, 'show']);
    Route::patch('/vehicle-model/{vehicleModel}', [VehicleModelController::class, 'update']);
    Route::post('/vehicle-model/activate/{vehicleModel}', [VehicleModelController::class, 'activate']);

    Route::get('/vehicles', [VehicleController::class, 'index']);
    Route::post('/vehicles', [VehicleController::class, 'store']);
    Route::get('/vehicle/{vehicle}', [VehicleController::class, 'show']);
    Route::patch('/vehicle/{vehicle}', [VehicleController::class, 'update']);
    Route::post('/vehicle/activate/{vehicle}', [VehicleController::class, 'activate']);
    Route::get('/vehicle/by-plate/{plate}', [VehicleController::class, 'findByPlate']);

    Route::get('/receptions', [ReceptionController::class, 'index']);
    Route::post('/receptions', [ReceptionController::class, 'store']);
    Route::get('/reception/{reception}', [ReceptionController::class, 'show']);
    Route::patch('/reception/{reception}', [ReceptionController::class, 'update']);
    Route::post('/reception/activate/{reception}', [ReceptionController::class, 'activate']);

    Route::get('/checklists', [CheckListController::class, 'index']);
    Route::post('/checklists', [CheckListController::class, 'store']);
    Route::get('/checklist/{checkListItem}', [CheckListController::class, 'show']);
    Route::patch('/checklist/{checkListItem}', [CheckListController::class, 'update']);
    Route::post('/checklist/activate/{checkListItem}', [CheckListController::class, 'activate']);

    Route::get('/reception-check-lists/{receptionCheckList}', [ReceptionCheckListController::class, 'show']);
    Route::patch('/reception-check-lists/{receptionCheckList}', [ReceptionCheckListController::class, 'update']);
    
});