# TODO — ERP Implementation Checklist

> Status: [ ] = pending, [x] = done, [~] = in progress

---

## Phase 1: Foundation
- [x] Laravel project setup (Laravel 11, PHP 8.3) + all packages installed
- [x] Next.js project setup (App Router, TypeScript, Tailwind, shadcn/ui, Recharts, TanStack Query v5)
- [x] Modular folder structure created (`app/Modules/`)
- [x] All migrations written (see database.md)
- [x] All seeders: roles, permissions, demo company, sample data
- [x] `AppServiceProvider`: bind all Repository interfaces to implementations
- [x] Base classes: `BaseController`, `BaseService`, `BaseRepository`
- [x] Helper: `money()` cents formatter, `toCents()` converter

📚 Dev Guide — Foundation:
The modular structure (`app/Modules/{Name}/Controllers|Services|Repositories|Models|Requests|Resources|routes.php`)
keeps each domain self-contained. Binding interfaces in AppServiceProvider enables
dependency injection and makes testing easy (swap real repos for fakes).
Study: Laravel 11 service container, interface binding, Repository pattern,
PHP 8.3 features (readonly properties, typed class constants), Laravel module organization patterns.

---

## Phase 2: Auth & RBAC
- [x] `POST /api/auth/login` — return Sanctum token
- [x] `POST /api/auth/logout`
- [x] `GET  /api/auth/me` — return user + roles + permissions array
- [x] Spatie roles seeded: Super Admin, Accountant, HR Manager, Warehouse, Sales Rep, Purchasing, Viewer
- [x] Permission matrix seeded (per module per role — see Architecture.md)
- [x] Next.js: login page + auth context (`useAuth` hook)
- [x] Protected layout: redirect unauthenticated users
- [x] Sidebar renders only permitted nav items based on permissions from `/me`

📚 Dev Guide — Auth + RBAC:
Spatie Laravel Permission stores roles/permissions in DB tables with pivot links.
`$user->assignRole('accountant')` and `$user->givePermissionTo('view-accounting')`.
In middleware: `$this->middleware('permission:view-hr')`.
In Next.js: after login, `/auth/me` returns `{ user, permissions: string[] }`.
Store in React Context (or Zustand). Sidebar maps permission strings to nav items using
`usePermissions()` hook. Never trust frontend auth alone — always check middleware on backend.
Study: Spatie docs, Laravel Sanctum SPA auth vs token auth, RBAC vs ABAC patterns,
JWT vs Sanctum token storage security (httpOnly cookie vs localStorage trade-offs).

---

## Phase 3: HR Module
- [x] Departments CRUD (`/api/hr/departments`)
- [x] Positions CRUD (`/api/hr/positions`)
- [x] Employees CRUD with document upload (`/api/hr/employees`)
- [x] Attendance: clock in/out API + manual entry (`/api/hr/attendance`)
- [x] Leave types CRUD + Leave requests with approval flow
- [x] Payroll: calculate net pay per employee per month
- [x] Payslip PDF generation (queued job)
- [x] Frontend: employees list, employee profile page, attendance calendar, leave management

📚 Dev Guide — Payroll Calculation:
`net_pay = base_salary + SUM(earnings) - SUM(deductions)`.
Store each component in `payroll_items` (type: earning/deduction, description, amount).
Never hardcode formulas — make components configurable. PDF: DomPDF renders a Blade view
→ dispatch `GeneratePayslipJob` → job stores PDF in Storage → notifies user.
Leave approval is a state machine: `pending → approved/rejected` (one-way transitions).
Study: Laravel DomPDF (`barryvdh/laravel-dompdf`), Laravel Jobs & Queues with Redis,
state machine pattern in PHP, Carbon date calculations for attendance/leave duration.

---

## Phase 4: Accounting Foundation
- [x] Chart of Accounts CRUD (parent-child hierarchy, account types)
- [x] Manual Journal Entry CRUD (debit/credit lines)
- [x] Validation: `sum(debits) === sum(credits)` enforced in Service, throws if unbalanced
- [x] Journal Entry list with filters (date, type, status)
- [x] Post journal entry (draft → posted)
- [x] Frontend: chart of accounts tree, journal entry form with dynamic lines

📚 Dev Guide — Double-Entry Accounting:
Every financial event = one journal entry with ≥ 2 lines where debits = credits exactly.
Example — customer pays invoice:
  `DR Cash (Asset)            +1000`
  `CR Accounts Receivable     -1000`
Amounts stored as integers (cents). Account hierarchy: `parent_id` self-reference.
`AccountingService::createEntry(array $lines)` validates balance then wraps in `DB::transaction()`.
Trial Balance = sum of all account balances. P&L = SUM(income accounts) - SUM(expense accounts).
Study: double-entry bookkeeping fundamentals, Chart of Accounts standard numbering (1xxx assets,
2xxx liabilities, 3xxx equity, 4xxx income, 5xxx expenses), Laravel DB transactions.

---

## Phase 5: Inventory Module
- [x] Products CRUD (SKU, barcode, UOM, reorder point, cost price, selling price)
- [x] Warehouses CRUD
- [x] Stock Movement: IN, OUT, TRANSFER endpoints
- [x] Current stock level computed from movements (never stored as column)
- [x] Low stock alert on every OUT movement (queued notification)
- [x] FIFO valuation: cost of goods sold computed from `stock_layers`
- [x] Frontend: products list, warehouse stock view, movement history

📚 Dev Guide — FIFO Inventory:
Never store "current stock" as a direct column — derive it from:
`SELECT SUM(CASE WHEN type='in' THEN qty ELSE -qty END) FROM stock_movements WHERE product_id=? AND warehouse_id=?`
This immutable ledger approach gives full audit trail and prevents negative stock bugs.
FIFO: when selling, cost = oldest unreduced layer's `cost_per_unit`.
`stock_layers` table: each IN creates a layer; each OUT reduces layers FIFO until qty satisfied.
Study: inventory valuation methods (FIFO vs AVCO vs Standard Cost), SQL window functions
for running totals, Laravel Observers to auto-trigger low-stock checks after OUT movements.

---

## Phase 6: Sales Module
- [x] Customers CRUD (contact, credit limit, balance)
- [x] Quotations CRUD (line items, discount, tax)
- [x] Convert Quotation → Sales Order (status machine)
- [x] Convert Sales Order → Invoice
- [x] Invoice PDF generation (queued)
- [x] Record payment (partial/full) → auto-creates AR journal entry
- [ ] Sales reports (by customer, product, period)
- [x] Frontend: customer list, quotation builder, invoice view, payment modal

📚 Dev Guide — Sales Pipeline State Machine:
Each document (Quotation/SalesOrder/Invoice) has a `status` field with allowed transitions only:
`draft → sent → accepted` (Quotation), `draft → confirmed → invoiced` (Sales Order), `unpaid → partial → paid` (Invoice).
When invoice confirmed:
  1. `StockService::deductStock()` — creates OUT movement + reduces FIFO layers
  2. `AccountingService::createEntry()`:
     `DR Accounts Receivable / CR Revenue`
     `DR Cost of Goods Sold / CR Inventory`
When payment recorded:
  3. `AccountingService::createEntry()`: `DR Cash / CR Accounts Receivable`
All 3 steps wrapped in `DB::transaction()`.
Study: state machine pattern (`asantibanez/laravel-eloquent-state-machines`),
Laravel Observers, database transactions for multi-step side effects.

---

## Phase 7: Purchasing Module
- [x] Vendors CRUD
- [x] Purchase Request → Purchase Order workflow
- [x] Goods Received Note (GRN) — confirms stock IN + auto-creates vendor bill
- [x] Vendor bill payment → auto-creates AP journal entry
- [x] Purchase reports
- [x] Frontend: vendor list, PO form, GRN confirmation, bill payment

📚 Dev Guide — GRN & Auto-Journal:
Three-way matching: PO (what was ordered) + GRN (what was received) + Bill (what was invoiced).
When GRN confirmed (all atomic in `DB::transaction()`):
  1. `StockService::addStock()` — creates IN movement + new FIFO layer
  2. `VendorBill` auto-created (status: unpaid, total = GRN qty × unit_cost)
  3. `AccountingService::createEntry()`: `DR Inventory / CR Accounts Payable`
When vendor paid:
  4. `AccountingService::createEntry()`: `DR Accounts Payable / CR Cash`
Study: three-way matching concept, Laravel DB transactions, Eloquent `created/updated` events
for cascading side effects, idempotency to prevent double-processing.

---

## Phase 8: POS Module
- [x] Open POS session with cash float entry
- [x] POS terminal: product search/scan, add to cart, quantity, discount
- [x] Multiple payment methods (cash, card, split)
- [x] Complete transaction → stock deduction + journal entry
- [x] Close session + reconciliation report (expected vs actual cash)
- [x] Frontend: POS terminal UI (keyboard-first, large tap targets for tablet)

📚 Dev Guide — POS Integration:
POS = Inventory + Accounting + Sales intersecting. Each transaction:
  1. For each line: `StockService::deductStock()`
  2. `AccountingService::createEntry()`:
     `DR Cash / CR Revenue`
     `DR COGS / CR Inventory`
  3. `PosSession::expected_cash += cash_payment_amount`
Session: `pos_sessions` has `opening_float`, tracks all cash in/out.
Reconciliation = `opening_float + total_cash_sales` vs `actual_cash` counted at close.
Use idempotency keys (`transaction_number` unique) to prevent duplicate transactions.
Study: POS UX (keyboard-first, barcode via `keypress` events), optimistic UI updates
in React for fast cart interactions, TanStack Query mutations with rollback.

---

## Phase 9: Financial Reports
- [x] Trial Balance (all accounts, debit/credit totals, balance)
- [x] Income Statement / P&L (revenue groups - expense groups = net profit)
- [x] Balance Sheet (assets = liabilities + equity)
- [x] AR Aging report (bucket by 0-30, 31-60, 61-90, 90+ days overdue)
- [x] AP Aging report
- [ ] Export all reports to PDF (DomPDF, queued) and Excel (maatwebsite/excel)
- [x] Frontend: report pages with date range filters, print-friendly layout

📚 Dev Guide — Financial Report Queries:
Reports are aggregate SQL queries grouped by account type.
Trial Balance: `SELECT accounts.code, accounts.name, SUM(journal_lines.debit), SUM(journal_lines.credit) FROM journal_lines JOIN accounts ... GROUP BY accounts.id`
AR Aging: `SELECT customer, DATEDIFF(NOW(), due_date) as days_overdue, SUM(outstanding) GROUP BY CASE WHEN days_overdue <= 30 THEN '0-30' ... END`
Use MySQL views for complex report queries — keeps PHP code clean.
maatwebsite/excel: implement `FromQuery` + `WithHeadings` interfaces.
Always queue PDF/Excel exports for datasets > 1000 rows.
Study: MySQL aggregate functions, GROUP BY with CASE, Laravel Excel docs,
financial statement structure (the accounting equation: Assets = Liabilities + Equity).

---

## Phase 10: Dashboard
- [ ] KPI cards: Revenue MTD, Expenses MTD, Net Profit, Outstanding AR, Outstanding AP
- [ ] Revenue trend chart — last 12 months (Recharts LineChart)
- [ ] Top 5 products by revenue (Recharts BarChart)
- [ ] Top 5 customers by revenue
- [ ] Low stock alerts widget (products below reorder point)
- [ ] Recent transactions feed (last 10 invoices + POS transactions)

---

## Phase 11: Polish
- [ ] RTL layout support (Arabic, font: Cairo from Google Fonts)
- [ ] Dark mode (Tailwind `dark:` classes + theme toggle)
- [ ] Mobile responsive layouts (POS especially — tablet-optimized)
- [ ] Loading skeletons for all data tables and cards
- [ ] Error handling + toast notifications (sonner or shadcn Toast)
- [ ] API rate limiting (Laravel throttle middleware)
- [ ] Input sanitization (FormRequests + XSS prevention)
- [ ] Pagination on all list endpoints
