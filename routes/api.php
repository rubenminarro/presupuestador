<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserRoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientVehicleController;
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
use App\Http\Controllers\Api\WorkOrderPartController;
use App\Http\Controllers\Api\WorkOrderItemMechanicController;
use App\Http\Controllers\Api\MechanicCommissionController;
use App\Http\Controllers\Api\ServiceCategoryController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvoiceStampController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PartCategoryController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PartController;
use App\Http\Controllers\Api\PartSupplierController;
use App\Http\Controllers\Api\InventoryStockController;
use App\Http\Controllers\Api\InventoryMovementController;
use App\Http\Controllers\Api\InventoryOperationController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->scopeBindings()->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('users', UserRoleController::class);

    Route::apiResource('permissions', PermissionController::class);

    // Antes del apiResource: si no, roles/{role} captura la URL.
    Route::get('/roles/permissions-grouped-by-module', [RoleController::class, 'permissionsGroupedByModule'])->name('roles.permissions-grouped-by-module');

    Route::apiResource('roles', RoleController::class);

    Route::apiResource('mechanics', MechanicController::class);

    Route::apiResource('clients', ClientController::class);

    Route::apiResource('clients.vehicles', ClientVehicleController::class)->only(['index', 'store', 'show']);

    Route::apiResource('brands', BrandController::class);

    Route::apiResource('vehicle-models', VehicleModelController::class);

    Route::apiResource('vehicles', VehicleController::class);

    Route::apiResource('checklists', CheckListController::class);

    Route::apiResource('receptions', ReceptionController::class);

    Route::get('/reception-check-lists/{receptionCheckList}', [ReceptionCheckListController::class, 'show'])->name('reception-check-lists.show');
        
    Route::patch('/reception-check-lists/{receptionCheckList}', [ReceptionCheckListController::class, 'update'])->name('reception-check-lists.update');

    Route::apiResource('receptions.photos', ReceptionPhotoController::class)->except(['show']);

    Route::apiResource('diagnostics', DiagnosticController::class);

    Route::apiResource('diagnostic-items', DiagnosticItemController::class);

    Route::apiResource('diagnostic-items.photos', DiagnosticItemPhotoController::class)->except(['show']);

    Route::apiResource('budgets', BudgetController::class);

    Route::post('budgets/{budget}/send', [BudgetController::class, 'send'])->name('budgets.send');

    Route::post('budgets/{budget}/approve', [BudgetController::class, 'approve'])->name('budgets.approve');

    Route::post('budgets/{budget}/reject', [BudgetController::class, 'reject'])->name('budgets.reject');

    Route::post('budgets/{budget}/cancel', [BudgetController::class, 'cancel'])->name('budgets.cancel');

    Route::post('budgets/{budget}/reopen', [BudgetController::class, 'reopen'])->name('budgets.reopen');

    Route::apiResource('budgets.items', BudgetItemController::class);

    Route::apiResource('work-orders', WorkOrderController::class)->except(['update', 'destroy']);

    Route::patch('work-orders/{work_order}', [WorkOrderController::class, 'update'])->name('work-orders.update');

    Route::post('work-orders/{workOrder}/start', [WorkOrderController::class, 'start'])->name('work-orders.start');

    Route::post('work-orders/{workOrder}/pause', [WorkOrderController::class, 'pause'])->name('work-orders.pause');

    Route::post('work-orders/{workOrder}/resume', [WorkOrderController::class, 'resume'])->name('work-orders.resume');

    Route::post('work-orders/{workOrder}/complete', [WorkOrderController::class, 'complete'])->name('work-orders.complete');

    Route::post('work-orders/{workOrder}/cancel', [WorkOrderController::class, 'cancel'])->name('work-orders.cancel');

    Route::post('work-orders/{workOrder}/items/{item}/start', [WorkOrderItemController::class, 'start'])->name('work-orders.items.start');

    Route::post('work-orders/{workOrder}/items/{item}/complete', [WorkOrderItemController::class, 'complete'])->name('work-orders.items.complete');

    Route::post('work-orders/{workOrder}/items/{item}/cancel', [WorkOrderItemController::class, 'cancel'])->name('work-orders.items.cancel');

    Route::get('work-orders/{workOrder}/items/{item}/mechanics', [WorkOrderItemMechanicController::class, 'index'])->name('work-orders.items.mechanics.index');

    Route::put('work-orders/{workOrder}/items/{item}/mechanics', [WorkOrderItemMechanicController::class, 'sync'])->name('work-orders.items.mechanics.sync');

    Route::patch('work-orders/{workOrder}/items/{item}/mechanics/{mechanic}/hours', [WorkOrderItemMechanicController::class, 'logHours'])->name('work-orders.items.mechanics.hours');

    Route::get('mechanics/{mechanic}/commissions', [MechanicCommissionController::class, 'index'])->name('mechanics.commissions.index');

    Route::post('mechanics/{mechanic}/commissions/settle', [MechanicCommissionController::class, 'settle'])->name('mechanics.commissions.settle');

    Route::get('invoice-stamps', [InvoiceStampController::class, 'index'])->name('invoice-stamps.index');

    Route::post('invoice-stamps', [InvoiceStampController::class, 'store'])->name('invoice-stamps.store');

    Route::patch('invoice-stamps/{invoiceStamp}', [InvoiceStampController::class, 'update'])->name('invoice-stamps.update');

    Route::post('work-orders/{workOrder}/invoice', [InvoiceController::class, 'storeFromWorkOrder'])->name('work-orders.invoice.store');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');

    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

    Route::patch('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');

    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');

    Route::patch('invoices/{invoice}/items/{item}', [InvoiceController::class, 'updateItem'])->name('invoices.items.update');

    Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');

    Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');

    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('invoices.payments.store');

    // Antes de payments/{payment}: si no, la ruta con parámetro captura "summary".
    Route::get('payments/summary', [PaymentController::class, 'summary'])->name('payments.summary');

    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');

    Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');

    Route::post('payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');

    Route::get('service-categories', [ServiceCategoryController::class, 'index'])->name('service-categories.index');

    Route::apiResource('services', ServiceController::class)->except(['update']);

    Route::patch('services/{service}', [ServiceController::class, 'update'])->name('services.update');

    Route::post('services/{service}/activate', [ServiceController::class, 'activate'])->name('services.activate');

    Route::post('services/{service}/deactivate', [ServiceController::class, 'deactivate'])->name('services.deactivate');

    Route::get('work-orders/{workOrder}/parts', [WorkOrderPartController::class, 'index'])->name('work-orders.parts.index');

    Route::post('work-orders/{workOrder}/parts', [WorkOrderPartController::class, 'store'])->name('work-orders.parts.store');

    Route::patch('work-orders/{workOrder}/parts/{part}', [WorkOrderPartController::class, 'update'])->name('work-orders.parts.update');

    Route::post('work-orders/{workOrder}/parts/{part}/reserve', [WorkOrderPartController::class, 'reserve'])->name('work-orders.parts.reserve');

    Route::post('work-orders/{workOrder}/parts/{part}/consume', [WorkOrderPartController::class, 'consume'])->name('work-orders.parts.consume');

    Route::post('work-orders/{workOrder}/parts/{part}/return', [WorkOrderPartController::class, 'returnToStock'])->name('work-orders.parts.return');

    Route::post('work-orders/{workOrder}/parts/{part}/cancel', [WorkOrderPartController::class, 'cancel'])->name('work-orders.parts.cancel');

    Route::apiResource('part-categories', PartCategoryController::class)->except(['update']);

    Route::patch('part-categories/{part_category}', [PartCategoryController::class, 'update'])->name('part-categories.update');

    Route::apiResource('suppliers', SupplierController::class)->except(['update']);

    Route::patch('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');

    Route::apiResource('parts', PartController::class)->except(['update']);

    Route::patch('parts/{part}', [PartController::class, 'update'])->name('parts.update');

    Route::post('parts/{part}/activate', [PartController::class, 'activate'])->name('parts.activate');

    Route::post('parts/{part}/deactivate', [PartController::class, 'deactivate'])->name('parts.deactivate');

    Route::post('parts/{part}/discontinue', [PartController::class, 'discontinue'])->name('parts.discontinue');

    Route::post('parts/{part}/suppliers', [PartSupplierController::class, 'store'])->name('parts.suppliers.store');

    Route::patch('parts/{part}/suppliers/{supplier}', [PartSupplierController::class, 'update'])->name('parts.suppliers.update');

    Route::delete('parts/{part}/suppliers/{supplier}', [PartSupplierController::class, 'destroy'])->name('parts.suppliers.destroy');

    Route::get('parts/{part}/movements', [InventoryMovementController::class, 'partMovements'])->name('parts.movements.index');

    Route::get('inventory/stocks', [InventoryStockController::class, 'index'])->name('inventory.stocks.index');

    Route::get('inventory/movements', [InventoryMovementController::class, 'index'])->name('inventory.movements.index');

    Route::get('inventory/movements/{movement}', [InventoryMovementController::class, 'show'])->name('inventory.movements.show');

    Route::post('inventory/entries', [InventoryOperationController::class, 'storeEntry'])->name('inventory.entries.store');

    Route::post('inventory/exits', [InventoryOperationController::class, 'storeExit'])->name('inventory.exits.store');

    Route::post('inventory/adjustments', [InventoryOperationController::class, 'storeAdjustment'])->name('inventory.adjustments.store');

});
