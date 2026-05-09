<?php

use App\Modules\Purchasing\Controllers\VendorController;
use App\Modules\Purchasing\Controllers\PurchaseOrderController;
use App\Modules\Purchasing\Controllers\GrnController;
use App\Modules\Purchasing\Controllers\VendorBillController;
use Illuminate\Support\Facades\Route;

Route::prefix('purchasing')->middleware('permission:view-purchasing')->group(function () {
    Route::apiResource('vendors',         VendorController::class);
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{id}/send', [PurchaseOrderController::class, 'send']);

    Route::apiResource('grn', GrnController::class);
    Route::post('grn/{id}/confirm', [GrnController::class, 'confirm'])
        ->middleware('permission:manage-vendors');

    Route::apiResource('bills',     VendorBillController::class);
    Route::post('bill-payments',    [VendorBillController::class, 'pay'])
        ->middleware('permission:manage-vendors');
});
