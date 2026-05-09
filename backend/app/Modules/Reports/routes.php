<?php

use App\Modules\Reports\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->middleware('permission:view-reports')->group(function () {
    Route::get('/dashboard',  [DashboardController::class, 'index']);
    Route::get('/kpis',       [DashboardController::class, 'kpis']);
    Route::get('/revenue-trend',    [DashboardController::class, 'revenueTrend']);
    Route::get('/top-products',     [DashboardController::class, 'topProducts']);
    Route::get('/top-customers',    [DashboardController::class, 'topCustomers']);
    Route::get('/low-stock',        [DashboardController::class, 'lowStock']);
    Route::get('/recent-activity',  [DashboardController::class, 'recentActivity']);
});
