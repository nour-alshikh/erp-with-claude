<?php

use App\Modules\Accounting\Controllers\AccountController;
use App\Modules\Accounting\Controllers\JournalEntryController;
use App\Modules\Accounting\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('accounting')->middleware('permission:view-accounting')->group(function () {
    Route::apiResource('accounts',        AccountController::class)->except(['destroy']);
    Route::apiResource('journal-entries', JournalEntryController::class);
    Route::post('journal-entries/{id}/post', [JournalEntryController::class, 'post'])
        ->middleware('permission:manage-journals');

    Route::prefix('reports')->middleware('permission:view-reports')->group(function () {
        Route::get('/trial-balance',    [ReportController::class, 'trialBalance']);
        Route::get('/income-statement', [ReportController::class, 'incomeStatement']);
        Route::get('/balance-sheet',    [ReportController::class, 'balanceSheet']);
        Route::get('/ar-aging',         [ReportController::class, 'arAging']);
        Route::get('/ap-aging',         [ReportController::class, 'apAging']);
        Route::get('/export/{type}',    [ReportController::class, 'export']);
    });
});
