<?php

use App\Modules\HR\Controllers\DepartmentController;
use App\Modules\HR\Controllers\EmployeeController;
use App\Modules\HR\Controllers\AttendanceController;
use App\Modules\HR\Controllers\LeaveController;
use App\Modules\HR\Controllers\PayrollController;
use Illuminate\Support\Facades\Route;

Route::prefix('hr')->middleware('permission:view-hr')->group(function () {
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('positions',   PositionController::class);
    Route::apiResource('employees',   EmployeeController::class);

    Route::prefix('attendance')->group(function () {
        Route::get('/',           [AttendanceController::class, 'index']);
        Route::post('/clock-in',  [AttendanceController::class, 'clockIn']);
        Route::post('/clock-out', [AttendanceController::class, 'clockOut']);
        Route::post('/manual',    [AttendanceController::class, 'manual'])->middleware('permission:manage-employees');
    });

    Route::prefix('leaves')->group(function () {
        Route::get('/',                        [LeaveController::class, 'index']);
        Route::post('/',                       [LeaveController::class, 'store']);
        Route::put('/{id}/approve',            [LeaveController::class, 'approve'])->middleware('permission:manage-employees');
        Route::put('/{id}/reject',             [LeaveController::class, 'reject'])->middleware('permission:manage-employees');
        Route::apiResource('types',            LeaveTypeController::class)->middleware('permission:manage-employees');
    });

    Route::prefix('payroll')->middleware('permission:manage-payroll')->group(function () {
        Route::get('/',                    [PayrollController::class, 'index']);
        Route::post('/run',               [PayrollController::class, 'run']);
        Route::get('/{id}',               [PayrollController::class, 'show']);
        Route::post('/{id}/approve',      [PayrollController::class, 'approve']);
        Route::get('/{id}/payslip/{emp}', [PayrollController::class, 'payslip']);
    });
});
