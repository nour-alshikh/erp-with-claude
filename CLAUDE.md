# CLAUDE.md — ERP Project

## Project Overview
Full-stack ERP built with Laravel 11 + Next.js 14.
Covers: HR, Accounting, Inventory, Sales, Purchasing, POS.

## Commands

### Backend (Laravel — run from `/backend`)
- `php artisan serve`                          — start API server (port 8000)
- `php artisan migrate:fresh --seed`           — reset and seed DB
- `php artisan queue:work --queue=high,default` — start worker
- `php artisan test`                           — run test suite
- `php artisan module:list`                    — list all modules

### Frontend (Next.js — run from `/frontend`)
- `npm run dev`    — start Next.js (port 3000)
- `npm run build`  — production build
- `npm run lint`   — ESLint check
- `npm run types`  — TypeScript check

## Architecture Decisions
- Modular Laravel: each ERP module in `app/Modules/{ModuleName}/`
- Repository + Service pattern throughout
- Spatie permission: one permission set per module per role
- All financial amounts stored as integers (cents) to avoid float precision
- `company_id` on every table (multi-company ready)
- Soft deletes everywhere
- All PDFs generated via queued jobs (async)
- Double-entry accounting: every transaction creates balanced journal entries

## Coding Standards
- Controllers: thin (delegate to Service — 5-10 lines max)
- Services: all business logic
- Repositories: all DB queries (no raw queries in Services)
- API Resources: all responses transformed via Resource classes
- FormRequests: all validation in dedicated Request classes
- Never use `DB::` directly in Services (use Repository)
- All amounts: store as integer (cents), display formatted via helper

## Environment Variables
See `.env.example` — required: DB_*, REDIS_*, MAIL_*, FILESYSTEM_DISK, APP_KEY

## Module List
- **Auth** — users, roles, permissions (Spatie)
- **HR** — employees, departments, positions, attendance, leaves, payroll
- **Accounting** — chart of accounts, journal entries, AP/AR, currencies, reports
- **Inventory** — products, warehouses, stock movements, FIFO layers
- **Sales** — customers, quotations, sales orders, invoices, payments received
- **Purchasing** — vendors, purchase orders, GRN, vendor bills, payments made
- **POS** — sessions, transactions, payments, reconciliation
- **Reports** — dashboard KPIs, financial statements, exports

## Key Helpers
- `money($cents)` — format integer cents to display string (e.g. 9999 → "99.99")
- `toCents($float)` — convert display value to cents integer
