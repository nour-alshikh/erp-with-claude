# Architecture — ERP System

## System Overview

```
┌──────────────────────────────────────────────────────────────────┐
│                     Next.js 14 (Frontend)                        │
│  App Router │ Server Components │ TanStack Query v5 │ Recharts   │
│  Tailwind CSS │ shadcn/ui │ TypeScript                           │
└─────────────────────────────┬────────────────────────────────────┘
                              │ HTTPS REST API
                              │ Authorization: Bearer {sanctum_token}
                              │ Content-Type: application/json
┌─────────────────────────────▼────────────────────────────────────┐
│                     Laravel 11 (Backend)                         │
│         Modular: app/Modules/{HR|Accounting|Sales|...}           │
│                                                                  │
│  Route → Sanctum Middleware → Permission Middleware              │
│       → FormRequest (validate) → Controller (thin)              │
│       → Service (business logic) → Repository (DB queries)      │
│       → API Resource (shape response) → JSON                    │
└──────────────┬───────────────┬──────────────┬────────────────────┘
               │               │              │
        ┌──────▼───┐   ┌───────▼────┐  ┌─────▼──────┐
        │ MySQL 8  │   │   Redis    │  │  Storage   │
        │ (main DB)│   │  (queues)  │  │  (files,   │
        │          │   │  (cache)   │  │   PDFs)    │
        └──────────┘   └─────┬──────┘  └────────────┘
                             │
                      ┌──────▼──────┐
                      │Queue Worker │
                      │ PDF exports │
                      │ Email notif │
                      │ Stock alerts│
                      │ Payslips    │
                      └─────────────┘
```

---

## Request Lifecycle (Laravel)

```
HTTP Request
  → cors middleware
  → Sanctum Auth Middleware     ← validates Bearer token, sets auth()->user()
  → Spatie Permission Middleware ← checks e.g. "permission:view-hr"
  → FormRequest                 ← validates & transforms input
  → Controller                  ← thin: calls Service, returns Resource
  → Service                     ← all business logic + DB::transaction()
  → Repository                  ← all Eloquent queries
  → API Resource                ← shapes the JSON response
  → JSON Response               ← 200/201/422/403 etc.
```

---

## Auto-Journal Entry Flow — Sales Invoice Confirmation

```
POST /api/sales/invoices/{id}/confirm
  └─> SalesController::confirm()
        └─> SalesService::confirmInvoice($invoice)
              ├─> DB::transaction() {
              │     foreach ($invoice->lines as $line) {
              │       StockService::deductStock(
              │         product_id: $line->product_id,
              │         warehouse_id: $defaultWarehouse,
              │         qty: $line->qty,
              │         reference: $invoice
              │       )
              │       ── creates stock_movements row (type: out)
              │       ── reduces stock_layers FIFO, captures COGS
              │     }
              │     AccountingService::createEntry([
              │       ['account' => AR_ACCOUNT,       'debit'  => $invoice->total],
              │       ['account' => REVENUE_ACCOUNT,  'credit' => $invoice->subtotal],
              │       ['account' => TAX_PAYABLE,      'credit' => $invoice->tax],
              │       ['account' => COGS_ACCOUNT,     'debit'  => $totalCogs],
              │       ['account' => INVENTORY_ACCOUNT,'credit' => $totalCogs],
              │     ], type: 'sale', reference: $invoice->invoice_number)
              │     $invoice->update(['status' => 'unpaid'])
              │   }
              └─> return InvoiceResource($invoice)
```

---

## Auto-Journal Flow — GRN Confirmation

```
POST /api/purchasing/grns/{id}/confirm
  └─> PurchasingService::confirmGrn($grn)
        └─> DB::transaction() {
              foreach ($grn->lines as $line) {
                StockService::addStock(product, warehouse, qty, cost_per_unit)
                ── creates stock_movements row (type: in)
                ── creates stock_layers row (for FIFO)
              }
              VendorBill::create([...from grn...])
              AccountingService::createEntry([
                ['account' => INVENTORY_ACCOUNT, 'debit'  => $grn->total],
                ['account' => AP_ACCOUNT,        'credit' => $grn->total],
              ], type: 'purchase')
            }
```

---

## POS Transaction Flow

```
POST /api/pos/transactions
  └─> PosService::createTransaction($session, $lines, $payments)
        └─> DB::transaction() {
              validate: session is open
              validate: sum(payments) >= total
              foreach ($lines as $line) {
                StockService::deductStock(...)
              }
              $transaction = PosTransaction::create([...])
              AccountingService::createEntry([
                ['account' => CASH_ACCOUNT,      'debit'  => $cashAmount],
                ['account' => CARD_ACCOUNT,      'debit'  => $cardAmount],
                ['account' => REVENUE_ACCOUNT,   'credit' => $subtotal],
                ['account' => TAX_PAYABLE,       'credit' => $tax],
                ['account' => COGS_ACCOUNT,      'debit'  => $totalCogs],
                ['account' => INVENTORY_ACCOUNT, 'credit' => $totalCogs],
              ], type: 'pos')
              $session->increment('expected_cash', $cashAmount)
            }
```

---

## Module Structure (Laravel)

```
backend/
  app/
    Modules/
      Auth/
        Controllers/
          AuthController.php
        Services/
          AuthService.php
        routes.php
      HR/
        Controllers/
          DepartmentController.php
          EmployeeController.php
          AttendanceController.php
          LeaveController.php
          PayrollController.php
        Services/
          HrService.php
          PayrollService.php
        Repositories/
          Interfaces/
            EmployeeRepositoryInterface.php
          EmployeeRepository.php
          AttendanceRepository.php
        Models/
          Employee.php
          Department.php
          Position.php
          Attendance.php
          LeaveRequest.php
          LeaveType.php
          PayrollRun.php
          PayrollItem.php
        Requests/
          StoreEmployeeRequest.php
          UpdateEmployeeRequest.php
        Resources/
          EmployeeResource.php
          PayslipResource.php
        Jobs/
          GeneratePayslipJob.php
        routes.php
      Accounting/
        Controllers/
          AccountController.php
          JournalEntryController.php
          ReportController.php
        Services/
          AccountingService.php    ← createEntry(), validateBalance()
        Repositories/
          AccountRepository.php
          JournalEntryRepository.php
        Models/
          Account.php
          JournalEntry.php
          JournalLine.php
          Currency.php
        routes.php
      Inventory/
        Controllers/
          ProductController.php
          WarehouseController.php
          StockMovementController.php
        Services/
          StockService.php         ← addStock(), deductStock(), transferStock()
        Repositories/
          ProductRepository.php
          StockMovementRepository.php
          StockLayerRepository.php
        Models/
          Product.php
          Warehouse.php
          StockMovement.php
          StockLayer.php
        Jobs/
          CheckLowStockJob.php
        routes.php
      Sales/
        Controllers/
          CustomerController.php
          QuotationController.php
          SalesOrderController.php
          InvoiceController.php
          PaymentController.php
        Services/
          SalesService.php         ← calls StockService + AccountingService
        Repositories/
          CustomerRepository.php
          InvoiceRepository.php
        Models/
          Customer.php
          Quotation.php
          QuotationLine.php
          SalesOrder.php
          SalesOrderLine.php
          Invoice.php
          InvoiceLine.php
          PaymentReceived.php
        Jobs/
          GenerateInvoicePdfJob.php
        routes.php
      Purchasing/
        Controllers/
          VendorController.php
          PurchaseOrderController.php
          GrnController.php
          VendorBillController.php
        Services/
          PurchasingService.php    ← calls StockService + AccountingService
        Models/
          Vendor.php
          PurchaseOrder.php
          PurchaseOrderLine.php
          GoodsReceivedNote.php
          GrnLine.php
          VendorBill.php
          PaymentMade.php
        routes.php
      POS/
        Controllers/
          PosSessionController.php
          PosTransactionController.php
        Services/
          PosService.php           ← calls StockService + AccountingService
        Models/
          PosSession.php
          PosTransaction.php
          PosTransactionLine.php
          PosPayment.php
        routes.php
      Reports/
        Controllers/
          DashboardController.php
          FinancialReportController.php
        Services/
          ReportService.php
        Exports/
          TrialBalanceExport.php
          PLExport.php
        routes.php
    Base/
      BaseController.php
      BaseService.php
      BaseRepository.php
    Helpers/
      MoneyHelper.php              ← money($cents), toCents($value)
```

---

## Frontend Module Structure (Next.js)

```
frontend/
  app/
    (auth)/
      login/
        page.tsx
    (dashboard)/
      layout.tsx                   ← sidebar + protected route wrapper
      page.tsx                     ← dashboard KPIs
      hr/
        employees/
          page.tsx                 ← employee list
          [id]/
            page.tsx               ← employee profile
          new/
            page.tsx
        attendance/
          page.tsx
        leaves/
          page.tsx
        payroll/
          page.tsx
      accounting/
        chart-of-accounts/
          page.tsx
        journal-entries/
          page.tsx
          new/
            page.tsx
        reports/
          trial-balance/
            page.tsx
          income-statement/
            page.tsx
          balance-sheet/
            page.tsx
      inventory/
        products/
          page.tsx
        warehouses/
          page.tsx
        movements/
          page.tsx
      sales/
        customers/
          page.tsx
        quotations/
          page.tsx
        orders/
          page.tsx
        invoices/
          page.tsx
      purchasing/
        vendors/
          page.tsx
        purchase-orders/
          page.tsx
        grn/
          page.tsx
        bills/
          page.tsx
      pos/
        page.tsx                   ← POS terminal (full-screen)
        sessions/
          page.tsx
  components/
    ui/                            ← shadcn/ui components
    layout/
      Sidebar.tsx
      Header.tsx
      PermissionGate.tsx           ← renders children only if user has permission
    shared/
      DataTable.tsx
      MoneyInput.tsx               ← handles cents conversion
      MoneyDisplay.tsx
      LoadingSkeleton.tsx
      ConfirmDialog.tsx
    charts/
      RevenueChart.tsx
      TopProductsChart.tsx
  lib/
    api/
      client.ts                    ← axios instance with auth token
      auth.ts
      hr.ts
      accounting.ts
      inventory.ts
      sales.ts
      purchasing.ts
      pos.ts
      reports.ts
    hooks/
      useAuth.ts                   ← login, logout, user state
      usePermissions.ts            ← hasPermission(key: string): boolean
      useMoney.ts                  ← format cents, parse input
    types/
      auth.d.ts
      hr.d.ts
      accounting.d.ts
      inventory.d.ts
      sales.d.ts
      purchasing.d.ts
      pos.d.ts
    utils/
      money.ts                     ← formatCents(), toCents()
      dates.ts
  providers/
    QueryProvider.tsx              ← TanStack Query client
    AuthProvider.tsx
    ThemeProvider.tsx
```

---

## Key Design Decisions

### 1. Amounts as Integer (Cents)
Store `$99.99` as `9999`. Prevents floating-point rounding bugs in financial calculations.
Always divide by 100 for display. Use `money()` helper on both frontend and backend.

### 2. Immutable Stock Ledger
Never update a stock quantity column directly. Always INSERT a new `stock_movement` row.
Current stock = `SUM(in movements) - SUM(out movements)`. Full audit trail, no negative stock bugs.

### 3. Double-Entry Enforcement
`AccountingService::createEntry()` validates `SUM(debits) === SUM(credits)` before saving.
Throws `UnbalancedJournalException` if violated. Always wrapped in `DB::transaction()`.

### 4. Modular Service Cross-calls
`SalesService` depends on `StockService` and `AccountingService` (injected via Laravel DI container).
Rule: never call a Repository from another module — always go through the module's Service.

### 5. Queue for Side Effects
PDF generation, email notifications, low-stock alerts → always dispatched as queued Jobs.
Never do heavy work (> 100ms) synchronously in an HTTP request.

### 6. Soft Deletes Everywhere
All models use `SoftDeletes` trait. Financial records are never hard-deleted.
Use `withTrashed()` only in audit/report contexts.

---

## Permissions Matrix

| Module       | Permission Keys                                          |
|--------------|----------------------------------------------------------|
| HR           | `view-hr`, `manage-employees`, `manage-payroll`          |
| Accounting   | `view-accounting`, `manage-journals`, `view-reports`     |
| Inventory    | `view-inventory`, `manage-products`, `manage-stock`      |
| Sales        | `view-sales`, `manage-invoices`, `manage-customers`      |
| Purchasing   | `view-purchasing`, `manage-pos`, `manage-vendors`        |
| POS          | `use-pos`, `manage-pos-sessions`                         |

### Role → Permission Assignment (abbreviated)
| Role        | Permissions                                                       |
|-------------|-------------------------------------------------------------------|
| Super Admin | all                                                               |
| Accountant  | view-accounting, manage-journals, view-reports                    |
| HR Manager  | view-hr, manage-employees, manage-payroll                         |
| Warehouse   | view-inventory, manage-stock                                      |
| Sales Rep   | view-sales, manage-invoices, manage-customers, use-pos            |
| Purchasing  | view-purchasing, manage-vendors, view-inventory                   |
| Viewer      | view-reports                                                      |

---

## API Conventions

- Base URL: `http://localhost:8000/api`
- Auth header: `Authorization: Bearer {token}`
- Success: `{ data: {...} }` or `{ data: [...], meta: {pagination} }`
- Validation error: `{ message: "...", errors: { field: ["msg"] } }` (HTTP 422)
- Unauthorized: HTTP 401/403
- All list endpoints: paginated (`?page=1&per_page=15`)
- All monetary fields: integers (cents) in requests and responses
- Dates: ISO 8601 (`YYYY-MM-DD`)
