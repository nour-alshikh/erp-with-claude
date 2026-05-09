<?php

use App\Modules\POS\Controllers\PosSessionController;
use App\Modules\POS\Controllers\PosTransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('pos')->middleware('permission:use-pos')->group(function () {
    Route::get('sessions',              [PosSessionController::class, 'index'])->middleware('permission:manage-pos-sessions');
    Route::post('sessions/open',        [PosSessionController::class, 'open']);
    Route::post('sessions/{id}/close',  [PosSessionController::class, 'close']);
    Route::get('sessions/current',      [PosSessionController::class, 'current']);

    Route::get('transactions',          [PosTransactionController::class, 'index']);
    Route::post('transactions',         [PosTransactionController::class, 'store']);
    Route::get('transactions/{id}',     [PosTransactionController::class, 'show']);
    Route::post('transactions/{id}/void', [PosTransactionController::class, 'void'])->middleware('permission:manage-pos-sessions');
});
