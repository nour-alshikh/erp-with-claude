<?php

use App\Modules\Reports\Controllers\DashboardController;
use App\Modules\Reports\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->middleware('permission:view-reports')->group(function () {
    // Dashboard (Phase 10)
    Route::get('/dashboard',       [DashboardController::class, 'index']);
    Route::get('/kpis',            [DashboardController::class, 'kpis']);
    Route::get('/revenue-trend',   [DashboardController::class, 'revenueTrend']);
    Route::get('/top-products',    [DashboardController::class, 'topProducts']);
    Route::get('/top-customers',   [DashboardController::class, 'topCustomers']);
    Route::get('/low-stock',       [DashboardController::class, 'lowStock']);
    Route::get('/recent-activity', [DashboardController::class, 'recentActivity']);

    // Financial Reports (Phase 9)
    Route::get('/trial-balance',    [ReportController::class, 'trialBalance']);
    Route::get('/income-statement', [ReportController::class, 'incomeStatement']);
    Route::get('/balance-sheet',    [ReportController::class, 'balanceSheet']);
    Route::get('/ar-aging',         [ReportController::class, 'arAging']);
    Route::get('/ap-aging',         [ReportController::class, 'apAging']);
});
