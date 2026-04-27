<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\UserRoleController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\ClientController;
use App\Http\Controllers\Api\Admin\BrandController;
use App\Http\Controllers\Api\Admin\BrandModelController;
use App\Http\Controllers\Api\Admin\VehicleController;

Route::middleware(['auth:sanctum', 'role:administrador'])->prefix('admin')->group(function () {
    
    Route::get('/users', [UserRoleController::class, 'index']);
    Route::post('/user', [UserRoleController::class, 'store']);
    Route::get('/user/show/{user}', [UserRoleController::class, 'show']);
    Route::put('/user/{user}', [UserRoleController::class, 'update']);
    Route::post('/user/activate/{user}', [UserRoleController::class, 'activate']);

    Route::get('permissions', [PermissionController::class, 'index']);
    Route::post('permission', [PermissionController::class, 'store']);
    Route::get('permission/show/{permission}', [PermissionController::class, 'show']);
    Route::put('permission/{permission}', [PermissionController::class, 'update']);
    Route::post('permission/activate/{permission}', [PermissionController::class, 'activate']);
    Route::delete('permission/{permission}', [PermissionController::class, 'destroy']);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/role', [RoleController::class, 'store']);
    Route::get('/role/show/{role}', [RoleController::class, 'show']);
    Route::put('/role/{role}', [RoleController::class, 'update']);
    Route::post('/role/activate/{role}', [RoleController::class, 'activate']);
    Route::delete('/role/{role}', [RoleController::class, 'destroy']);
    Route::get('/role/permissions', [RoleController::class, 'permissions']);
    
    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/client', [ClientController::class, 'store']);
    Route::get('/client/show/{client}', [ClientController::class, 'show']);
    Route::put('/client/{client}', [ClientController::class, 'update']);
    Route::post('/client/activate/{client}', [ClientController::class, 'activate']);

    Route::get('/brands', [BrandController::class, 'index']);
    Route::post('/brands', [BrandController::class, 'store']);
    Route::get('/brand/{brand}', [BrandController::class, 'show']);
    Route::put('/brand/{brand}', [BrandController::class, 'update']);
    Route::post('/brand/activate/{brand}', [BrandController::class, 'activate']);

    Route::get('/brand-models', [BrandModelController::class, 'index']);
    Route::post('/brand-models', [BrandModelController::class, 'store']);
    Route::get('/brand-model/{brandModel}', [BrandModelController::class, 'show']);
    Route::put('/brand-model/{brandModel}', [BrandModelController::class, 'update']);
    Route::post('/brand-model/activate/{brandModel}', [BrandModelController::class, 'activate']);

    Route::get('/vehicles', [VehicleController::class, 'index']);
    Route::post('/vehicles', [VehicleController::class, 'store']);
    Route::get('/vehicle/{vehicle}', [VehicleController::class, 'show']);
    Route::put('/vehicle/{vehicle}', [VehicleController::class, 'update']);
    Route::post('/vehicle/activate/{vehicle}', [VehicleController::class, 'activate']);
    Route::get('/vehicle/by-plate/{plate}', [VehicleController::class, 'findByPlate']);

});