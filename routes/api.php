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
use App\Http\Controllers\Api\MechanicController;
use App\Http\Controllers\Api\WorkOrderController;
use App\Http\Controllers\Api\WorkOrderItemController;

Route::middleware('auth:sanctum')->scopeBindings()->group(function () {

    Route::apiResource('users', UserRoleController::class);

    Route::apiResource('permissions', PermissionController::class);

    Route::apiResource('roles', RoleController::class);
    
    Route::get('/roles/permissions-grouped-by-module', [RoleController::class, 'permissionsGroupedByModule']);

    Route::apiResource('clients', ClientController::class);

    Route::apiResource('brands', BrandController::class);

    Route::apiResource('vehicle-models', VehicleModelController::class);

    Route::apiResource('vehicles', VehicleController::class);

    Route::apiResource('checklists', CheckListController::class);

    Route::apiResource('receptions', ReceptionController::class);

    Route::get('/reception-check-lists/{receptionCheckList}', [ReceptionCheckListController::class, 'show']);
    
    Route::patch('/reception-check-lists/{receptionCheckList}', [ReceptionCheckListController::class, 'update']);

    Route::apiResource('receptions.photos', ReceptionPhotoController::class)->except(['show']);

    Route::apiResource('diagnostics', DiagnosticController::class);

    Route::apiResource('diagnostic-items', DiagnosticItemController::class);

});


/*Route::middleware(['auth:sanctum', 'role:administrador'])->group(function () { 

    Route::get('/diagnostic-items', [DiagnosticItemController::class, 'index']);
    Route::post('/diagnostic-items', [DiagnosticItemController::class, 'store']);
    Route::get('/diagnostic-item/{diagnosticItem}', [DiagnosticItemController::class, 'show']);
    Route::patch('/diagnostic-item/{diagnosticItem}', [DiagnosticItemController::class, 'update']);
    Route::delete('/diagnostic-item/{diagnosticItem}', [DiagnosticItemController::class, 'destroy']);

    Route::scopeBindings()->group(function () {

        Route::get(
            '/diagnostic-item/{diagnosticItem}/photos',
            [DiagnosticItemPhotoController::class, 'index']
        );

        Route::post(
            '/diagnostic-item/{diagnosticItem}/photos',
            [DiagnosticItemPhotoController::class, 'store']
        );

        Route::patch(
            '/diagnostic-item/{diagnosticItem}/photos/{photo}',
            [DiagnosticItemPhotoController::class, 'update']
        );

        Route::delete(
            '/diagnostic-item/{diagnosticItem}/photos/{photo}',
            [DiagnosticItemPhotoController::class, 'destroy']
        );

    });

    Route::scopeBindings()->group(function () {

        Route::apiResource('budgets', BudgetController::class);

        Route::post(
            'budgets/{budget}/send',
            [BudgetController::class, 'send']
        )->name('budgets.send');

        Route::post(
            'budgets/{budget}/approve',
            [BudgetController::class, 'approve']
        )->name('budgets.approve');

        Route::post(
            'budgets/{budget}/reject',
            [BudgetController::class, 'reject']
        )->name('budgets.reject');

        Route::post(
            'budgets/{budget}/cancel',
            [BudgetController::class, 'cancel']
        )->name('budgets.cancel');

        Route::post(
            'budgets/{budget}/reopen',
            [BudgetController::class, 'reopen']
        )->name('budgets.reopen');

        Route::apiResource('budgets.items', BudgetItemController::class);

    });
    
    Route::scopeBindings()->group(function () {
        Route::apiResource('mechanics', MechanicController::class);
    });
    
    Route::scopeBindings()->group(function () {

        Route::get(
            'work-orders',
            [WorkOrderController::class, 'index']
        )->name('work-orders.index');

        Route::post(
            'work-orders',
            [WorkOrderController::class, 'store']
        )->name('work-orders.store');

        Route::get(
            'work-orders/{workOrder}',
            [WorkOrderController::class, 'show']
        )->name('work-orders.show');

        Route::patch(
            'work-orders/{workOrder}',
            [WorkOrderController::class, 'update']
        )->name('work-orders.update');


        Route::post(
            'work-orders/{workOrder}/start',
            [WorkOrderController::class, 'start']
        )->name('work-orders.start');

        Route::post(
            'work-orders/{workOrder}/pause',
            [WorkOrderController::class, 'pause']
        )->name('work-orders.pause');

        Route::post(
            'work-orders/{workOrder}/resume',
            [WorkOrderController::class, 'resume']
        )->name('work-orders.resume');

        Route::post(
            'work-orders/{workOrder}/complete',
            [WorkOrderController::class, 'complete']
        )->name('work-orders.complete');

        Route::post(
            'work-orders/{workOrder}/cancel',
            [WorkOrderController::class, 'cancel']
        )->name('work-orders.cancel');

        Route::post(
            'work-orders/{workOrder}/items/{item}/start',
            [WorkOrderItemController::class, 'start']
        )->name('work-orders.items.start');

        Route::post(
            'work-orders/{workOrder}/items/{item}/complete',
            [WorkOrderItemController::class, 'complete']
        )->name('work-orders.items.complete');

        Route::post(
            'work-orders/{workOrder}/items/{item}/cancel',
            [WorkOrderItemController::class, 'cancel']
        )->name('work-orders.items.cancel');

    });

});*/