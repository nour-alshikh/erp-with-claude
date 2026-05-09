<?php

use App\Modules\Sales\Controllers\CustomerController;
use App\Modules\Sales\Controllers\QuotationController;
use App\Modules\Sales\Controllers\SalesOrderController;
use App\Modules\Sales\Controllers\InvoiceController;
use App\Modules\Sales\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('sales')->middleware('permission:view-sales')->group(function () {
    Route::apiResource('customers',  CustomerController::class);
    Route::apiResource('quotations', QuotationController::class);
    Route::post('quotations/{id}/convert', [QuotationController::class, 'convertToOrder'])
        ->middleware('permission:manage-invoices');

    Route::apiResource('orders', SalesOrderController::class);
    Route::post('orders/{id}/invoice', [SalesOrderController::class, 'convertToInvoice'])
        ->middleware('permission:manage-invoices');

    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{id}/confirm', [InvoiceController::class, 'confirm'])
        ->middleware('permission:manage-invoices');
    Route::get('invoices/{id}/pdf',      [InvoiceController::class, 'pdf']);

    Route::post('payments', [PaymentController::class, 'store'])
        ->middleware('permission:manage-invoices');
});
