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
use App\Http\Controllers\Api\DiagnosticItemPhotoController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\BudgetItemController;

Route::middleware(['auth:sanctum', 'role:administrador'])->group(function () {
    
    Route::get('/users', [UserRoleController::class, 'index']);
    Route::post('/users', [UserRoleController::class, 'store']);
    Route::get('/user/{user}', [UserRoleController::class, 'show']);
    Route::patch('/user/{user}', [UserRoleController::class, 'update']);
    Route::delete('/user/{user}', [UserRoleController::class, 'destroy']);

    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::post('/permissions', [PermissionController::class, 'store']);
    Route::get('/permission/{permission}', [PermissionController::class, 'show']);
    Route::patch('/permission/{permission}', [PermissionController::class, 'update']);
    Route::delete('/permission/{permission}', [PermissionController::class, 'destroy']);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/role/{role}', [RoleController::class, 'show']);
    Route::patch('/role/{role}', [RoleController::class, 'update']);
    Route::delete('/role/{role}', [RoleController::class, 'destroy']);
    Route::get('/role/permissions-grouped-by-module', [RoleController::class, 'permissionsGroupedByModule']);
    
    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/client/{client}', [ClientController::class, 'show']);
    Route::patch('/client/{client}', [ClientController::class, 'update']);
    Route::delete('/client/{client}', [ClientController::class, 'destroy']);

    Route::get('/brands', [BrandController::class, 'index']);
    Route::post('/brands', [BrandController::class, 'store']);
    Route::get('/brand/{brand}', [BrandController::class, 'show']);
    Route::patch('/brand/{brand}', [BrandController::class, 'update']);
    Route::delete('/brand/{brand}', [BrandController::class, 'destroy']);

    Route::get('/vehicle-models', [VehicleModelController::class, 'index']);
    Route::post('/vehicle-models', [VehicleModelController::class, 'store']);
    Route::get('/vehicle-model/{vehicleModel}', [VehicleModelController::class, 'show']);
    Route::patch('/vehicle-model/{vehicleModel}', [VehicleModelController::class, 'update']);
    Route::delete('/vehicle-model/{vehicleModel}', [VehicleModelController::class, 'destroy']);

    Route::get('/vehicles', [VehicleController::class, 'index']);
    Route::post('/vehicles', [VehicleController::class, 'store']);
    Route::get('/vehicle/{vehicle}', [VehicleController::class, 'show']);
    Route::patch('/vehicle/{vehicle}', [VehicleController::class, 'update']);
    Route::delete('/vehicle/{vehicle}', [VehicleController::class, 'destroy']);

    Route::get('/checklists', [CheckListController::class, 'index']);
    Route::post('/checklists', [CheckListController::class, 'store']);
    Route::get('/checklist/{checkListItem}', [CheckListController::class, 'show']);
    Route::patch('/checklist/{checkListItem}', [CheckListController::class, 'update']);
    Route::delete('/checklist/{checkListItem}', [CheckListController::class, 'destroy']);
    
    Route::get('/receptions', [ReceptionController::class, 'index']);
    Route::post('/receptions', [ReceptionController::class, 'store']);
    Route::get('/reception/{reception}', [ReceptionController::class, 'show']);
    Route::patch('/reception/{reception}', [ReceptionController::class, 'update']);
    Route::delete('/reception/{reception}', [ReceptionController::class, 'destroy']);

    Route::get('/reception-check-lists/{receptionCheckList}', [ReceptionCheckListController::class, 'show']);
    Route::patch('/reception-check-lists/{receptionCheckList}', [ReceptionCheckListController::class, 'update']);

    Route::get('/receptions/{reception}/photos', [ReceptionPhotoController::class, 'index']);
    Route::post('/receptions/{reception}/photos', [ReceptionPhotoController::class, 'store']);
    Route::patch('/receptions/{reception}/photos/{photo}', [ReceptionPhotoController::class, 'update']);
    Route::delete('/receptions/{reception}/photos/{photo}', [ReceptionPhotoController::class, 'destroy']);

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

    Route::get('/diagnostic-item/{diagnosticItem}/photos', [DiagnosticItemPhotoController::class, 'index']);
    Route::post('/diagnostic-item/{diagnosticItem}/photos', [DiagnosticItemPhotoController::class, 'store']);
    Route::patch('/diagnostic-item/{diagnosticItem}/photos/{diagnosticItemPhoto}', [DiagnosticItemPhotoController::class, 'update']);
    Route::delete('/diagnostic-item/{diagnosticItem}/photos/{diagnosticItemPhoto}', [DiagnosticItemPhotoController::class, 'destroy']);

    Route::get('/budgets', [BudgetController::class, 'index']);
    Route::post('/budgets', [BudgetController::class, 'store']);
    Route::get('/budget/{budget}', [BudgetController::class, 'show']);
    Route::patch('/budget/{budget}', [BudgetController::class, 'update']);
    Route::delete('/budget/{budget}', [BudgetController::class, 'destroy']);

    Route::get('/budget/{budget}/items', [BudgetItemController::class, 'index']);
    Route::post('/budget/{budget}/items', [BudgetItemController::class, 'store']);
    Route::get('/budget/{budget}/item/{item}', [BudgetItemController::class, 'show']);
    Route::patch('/budget/{budget}/item/{item}', [BudgetItemController::class, 'update']);
    Route::delete('/budget/{budget}/item/{item}', [BudgetItemController::class, 'destroy']);

});


