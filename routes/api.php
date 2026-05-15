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
use App\Http\Controllers\Api\ReceptionPhotoController;
use App\Http\Controllers\Api\DiagnosticController;
use App\Http\Controllers\Api\DiagnosticItemController;

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

    Route::get('/receptions/{reception}/photos', [ReceptionPhotoController::class, 'index']);
    Route::post('/receptions/{reception}/photos', [ReceptionPhotoController::class, 'store']);
    Route::patch('/receptions/{reception}/photos/{receptionPhoto}', [ReceptionPhotoController::class, 'update']);
    Route::delete('/receptions/{reception}/photos/{receptionPhoto}', [ReceptionPhotoController::class, 'destroy']);

    Route::get('/diagnostics', [DiagnosticController::class, 'index']);
    Route::post('/diagnostics', [DiagnosticController::class, 'store']);
    Route::get('/diagnostic/{diagnostic}', [DiagnosticController::class, 'show']);
    Route::patch('/diagnostic/{diagnostic}', [DiagnosticController::class, 'update']);
    Route::delete('/diagnostic/{diagnostic}', [DiagnosticController::class, 'destroy']);

    Route::get('/diagnostic-items', [DiagnosticItemController::class, 'index']);
    Route::post('/diagnostic-items', [DiagnosticItemController::class, 'store']);
    Route::get('/diagnostic-item/{diagnosticItem}', [DiagnosticItemController::class, 'show']);
    Route::patch('/diagnostic-item/{diagnosticItem}', [DiagnosticItemController::class, 'update']);
    Route::delete('/diagnostic-item/{diagnosticItem}', [DiagnosticItemController::class, 'destroy']);

});


