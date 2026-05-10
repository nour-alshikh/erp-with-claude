<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── HR Module ────────────────────────────────────────────────────────
        $this->app->bind(
            \App\Modules\HR\Repositories\Interfaces\DepartmentRepositoryInterface::class,
            \App\Modules\HR\Repositories\DepartmentRepository::class,
        );
        $this->app->bind(
            \App\Modules\HR\Repositories\Interfaces\PositionRepositoryInterface::class,
            \App\Modules\HR\Repositories\PositionRepository::class,
        );
        $this->app->bind(
            \App\Modules\HR\Repositories\Interfaces\EmployeeRepositoryInterface::class,
            \App\Modules\HR\Repositories\EmployeeRepository::class,
        );
        $this->app->bind(
            \App\Modules\HR\Repositories\Interfaces\AttendanceRepositoryInterface::class,
            \App\Modules\HR\Repositories\AttendanceRepository::class,
        );
        $this->app->bind(
            \App\Modules\HR\Repositories\Interfaces\LeaveTypeRepositoryInterface::class,
            \App\Modules\HR\Repositories\LeaveTypeRepository::class,
        );
        $this->app->bind(
            \App\Modules\HR\Repositories\Interfaces\LeaveRequestRepositoryInterface::class,
            \App\Modules\HR\Repositories\LeaveRequestRepository::class,
        );
        $this->app->bind(
            \App\Modules\HR\Repositories\Interfaces\PayrollRunRepositoryInterface::class,
            \App\Modules\HR\Repositories\PayrollRunRepository::class,
        );

        // ── Accounting Module ────────────────────────────────────────────────
        $this->app->bind(
            \App\Modules\Accounting\Repositories\Interfaces\AccountRepositoryInterface::class,
            \App\Modules\Accounting\Repositories\AccountRepository::class,
        );
        $this->app->bind(
            \App\Modules\Accounting\Repositories\Interfaces\JournalEntryRepositoryInterface::class,
            \App\Modules\Accounting\Repositories\JournalEntryRepository::class,
        );

        // ── Inventory Module ─────────────────────────────────────────────────
        $this->app->bind(
            \App\Modules\Inventory\Repositories\Interfaces\ProductRepositoryInterface::class,
            \App\Modules\Inventory\Repositories\ProductRepository::class,
        );
        $this->app->bind(
            \App\Modules\Inventory\Repositories\Interfaces\StockMovementRepositoryInterface::class,
            \App\Modules\Inventory\Repositories\StockMovementRepository::class,
        );

        // ── Sales Module ─────────────────────────────────────────────────────
        $this->app->bind(
            \App\Modules\Sales\Repositories\Interfaces\InvoiceRepositoryInterface::class,
            \App\Modules\Sales\Repositories\InvoiceRepository::class,
        );

        // ── Purchasing Module ────────────────────────────────────────────────
        $this->app->bind(
            \App\Modules\Purchasing\Repositories\Interfaces\VendorBillRepositoryInterface::class,
            \App\Modules\Purchasing\Repositories\VendorBillRepository::class,
        );

        // ── POS Module ───────────────────────────────────────────────────────
        $this->app->bind(
            \App\Modules\POS\Repositories\Interfaces\PosSessionRepositoryInterface::class,
            \App\Modules\POS\Repositories\PosSessionRepository::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
