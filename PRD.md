# PRD — ERP Product Requirements Document

## 1. Purpose
A modular ERP system enabling businesses to manage HR, finances, inventory,
sales, and purchasing from a single unified platform with full audit trails
and double-entry accounting integrity.

## 2. Users & Roles

| Role          | Modules Access                                     |
|---------------|----------------------------------------------------|
| Super Admin   | Everything                                         |
| Accountant    | Accounting, AR/AP, Reports                         |
| HR Manager    | HR, Payroll, Attendance                            |
| Warehouse     | Inventory, GRN                                     |
| Sales Rep     | Sales, CRM, POS                                    |
| Purchasing    | Purchasing, Vendors                                |
| Viewer        | Reports read-only                                  |

## 3. Modules & Features

### 3.1 HR Module
- Employee profiles (personal info, contract details, document uploads)
- Department & position management
- Attendance (clock in/out API or manual entry by HR manager)
- Leave requests (configurable types, balance tracking, approval workflow)
- Payroll: base salary + allowances + deductions = net pay
- Payslip PDF generation (async, queued)

### 3.2 Accounting Module
- Chart of Accounts (assets, liabilities, equity, income, expense) with parent-child hierarchy
- Manual journal entries (debit/credit lines, must balance)
- Auto-generated journal entries from: Sales confirmations, PO receipts, POS transactions, payments
- Accounts Payable — vendor bills + payments
- Accounts Receivable — customer invoices + receipts
- Financial Reports: Trial Balance, P&L Statement, Balance Sheet
- Multi-currency support (configurable base currency, exchange rates)
- VAT / Tax management

### 3.3 Inventory Module
- Products with SKU, barcode, unit of measure, reorder point
- Multiple warehouses
- Stock movements: IN (purchase/GRN), OUT (sale/invoice), TRANSFER (between warehouses)
- Current stock derived from movement ledger (immutable, audit-safe)
- Low-stock alerts triggered on every OUT movement (queued notification)
- Inventory valuation via FIFO method (stock_layers table)

### 3.4 Sales Module
- Customer management (contact info, credit limit, balance tracking)
- Pipeline: Quotation → Sales Order → Invoice → Payment Receipt
- Invoice PDF generation + email (queued)
- Payment recording (partial or full) → auto-creates AR journal entry
- Sales reports by customer, product, date range

### 3.5 Purchasing Module
- Vendor management (contact info, outstanding balance)
- Purchase Request → Purchase Order → Goods Received Note workflow
- GRN confirmation auto-creates: stock IN movement + vendor bill
- Vendor bill payment → auto-creates AP journal entry
- Purchase reports by vendor, product, period

### 3.6 POS Module
- Fast sales terminal (barcode scanner support via keyboard input)
- Payment methods: Cash, Card, Split (multiple methods per transaction)
- Open session with opening cash float entry
- Close session with end-of-day reconciliation report
- Each POS sale auto: deducts stock + creates journal entry (DR Cash / CR Revenue)

### 3.7 Dashboard & Reports
- KPI cards: Revenue, Expenses, Net Profit, Outstanding AR, Outstanding AP
- Charts: monthly revenue trend (Recharts), top 5 products, top 5 customers
- Low stock alerts widget
- Recent transactions feed
- All reports exportable to PDF and Excel

## 4. Non-Functional Requirements
- API response time < 300ms for standard CRUD endpoints
- PDF/Excel generation: async via queued jobs (never block HTTP response)
- Financial data integrity: double-entry validation enforced at Service layer
- RTL (Arabic) UI support — font: Cairo, direction: RTL toggle
- Mobile-responsive layout (POS terminal must work on tablet)
- Multi-branch isolation: all queries scoped by `company_id`
- Soft deletes everywhere for audit compliance
- All amounts stored as integers (cents) — no floating-point in finance

## 5. Out of Scope (v1)
- Customer self-service portal
- E-commerce / Shopify integration
- Native mobile app (iOS/Android)
- Advanced BI / analytics (OLAP, data warehouse)
- Multi-language beyond Arabic/English
