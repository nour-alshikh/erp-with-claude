<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — ERP
|--------------------------------------------------------------------------
| Module routes are loaded from each module's routes.php file via
| AppServiceProvider. See app/Modules/{Module}/routes.php.
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/health', fn () => response()->json(['status' => 'ok']));

// Auth routes (public)
require base_path('app/Modules/Auth/routes.php');

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    require base_path('app/Modules/HR/routes.php');
    require base_path('app/Modules/Accounting/routes.php');
    require base_path('app/Modules/Inventory/routes.php');
    require base_path('app/Modules/Sales/routes.php');
    require base_path('app/Modules/Purchasing/routes.php');
    require base_path('app/Modules/POS/routes.php');
    require base_path('app/Modules/Reports/routes.php');
});
