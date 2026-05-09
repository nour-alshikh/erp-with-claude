<?php

use App\Modules\Inventory\Controllers\ProductController;
use App\Modules\Inventory\Controllers\WarehouseController;
use App\Modules\Inventory\Controllers\StockMovementController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->middleware('permission:view-inventory')->group(function () {
    Route::apiResource('products',   ProductController::class);
    Route::apiResource('warehouses', WarehouseController::class);

    Route::prefix('stock')->middleware('permission:manage-stock')->group(function () {
        Route::get('/',           [StockMovementController::class, 'index']);
        Route::post('/in',        [StockMovementController::class, 'stockIn']);
        Route::post('/out',       [StockMovementController::class, 'stockOut']);
        Route::post('/transfer',  [StockMovementController::class, 'transfer']);
        Route::get('/levels',     [StockMovementController::class, 'levels']);
        Route::get('/low-stock',  [StockMovementController::class, 'lowStock']);
    });
});
