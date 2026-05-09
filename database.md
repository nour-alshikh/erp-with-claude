# Database Schema — ERP

> All tables include: `id` (bigint PK), `company_id` (FK→companies), `created_at`, `updated_at`, `deleted_at` (soft delete)
> All monetary amounts stored as **INTEGER (cents)** — e.g. $99.99 stored as 9999
> All tables use InnoDB with foreign key constraints

---

## Core / Auth

### companies
| Column       | Type     | Notes                        |
|--------------|----------|------------------------------|
| name         | varchar  |                              |
| currency     | varchar  | default: USD                 |
| tax_rate     | decimal  | default percentage for VAT   |
| address      | text     |                              |
| is_active    | boolean  |                              |

### users
| Column      | Type    | Notes                         |
|-------------|---------|-------------------------------|
| name        | varchar |                               |
| email       | varchar | unique per company            |
| password    | varchar | bcrypt                        |
| is_active   | boolean | default: true                 |

*(Spatie tables: roles, permissions, model_has_roles, model_has_permissions, role_has_permissions)*

---

## HR Module

### departments
| Column      | Type    | Notes                         |
|-------------|---------|-------------------------------|
| name        | varchar |                               |
| manager_id  | FK      | → employees (nullable)        |

### positions
| Column        | Type    | Notes                       |
|---------------|---------|-----------------------------|
| name          | varchar |                             |
| department_id | FK      | → departments               |

### employees
| Column         | Type     | Notes                                     |
|----------------|----------|-------------------------------------------|
| user_id        | FK       | → users (nullable — not all have logins)  |
| name           | varchar  |                                           |
| national_id    | varchar  | unique per company                        |
| department_id  | FK       | → departments                             |
| position_id    | FK       | → positions                               |
| hire_date      | date     |                                           |
| base_salary    | integer  | cents                                     |
| status         | enum     | active / inactive / terminated            |

### attendance
| Column      | Type     | Notes                                   |
|-------------|----------|-----------------------------------------|
| employee_id | FK       | → employees                             |
| date        | date     |                                         |
| clock_in    | time     | nullable                                |
| clock_out   | time     | nullable                                |
| type        | enum     | present / absent / leave                |

### leave_types
| Column               | Type    | Notes              |
|----------------------|---------|--------------------|
| name                 | varchar | e.g. Annual, Sick  |
| days_allowed_per_year| integer |                    |

### leave_requests
| Column         | Type    | Notes                              |
|----------------|---------|------------------------------------|
| employee_id    | FK      | → employees                        |
| leave_type_id  | FK      | → leave_types                      |
| from_date      | date    |                                    |
| to_date        | date    |                                    |
| status         | enum    | pending / approved / rejected      |
| approved_by    | FK      | → users (nullable)                 |
| notes          | text    | nullable                           |

### payroll_runs
| Column     | Type    | Notes                            |
|------------|---------|----------------------------------|
| month      | tinyint | 1-12                             |
| year       | smallint|                                  |
| status     | enum    | draft / approved / paid          |

### payroll_items
| Column        | Type    | Notes                           |
|---------------|---------|---------------------------------|
| payroll_run_id| FK      | → payroll_runs                  |
| employee_id   | FK      | → employees                     |
| type          | enum    | earning / deduction             |
| description   | varchar | e.g. "Base Salary", "Transport" |
| amount        | integer | cents                           |

---

## Accounting Module

### accounts
| Column     | Type    | Notes                                                  |
|------------|---------|--------------------------------------------------------|
| code       | varchar | e.g. 1010 (numbering: 1xxx=asset, 2xxx=liab, etc.)    |
| name       | varchar |                                                        |
| type       | enum    | asset / liability / equity / income / expense          |
| parent_id  | FK      | → accounts (self-ref, nullable for root)               |
| is_active  | boolean |                                                        |

### journal_entries
| Column      | Type    | Notes                                                   |
|-------------|---------|---------------------------------------------------------|
| date        | date    |                                                         |
| reference   | varchar | e.g. INV-2024-001, POS-0042                            |
| description | text    |                                                         |
| type        | enum    | manual / sale / purchase / payment / pos                |
| status      | enum    | draft / posted                                          |

### journal_lines
| Column           | Type    | Notes                            |
|------------------|---------|----------------------------------|
| journal_entry_id | FK      | → journal_entries                |
| account_id       | FK      | → accounts                       |
| debit            | integer | cents (0 if credit line)         |
| credit           | integer | cents (0 if debit line)          |
| description      | varchar | nullable line-level note         |

> Constraint: for any journal_entry, SUM(debit) MUST equal SUM(credit) — enforced in Service layer

### currencies
| Column        | Type    | Notes                           |
|---------------|---------|---------------------------------|
| code          | varchar | ISO 4217 e.g. USD, SAR, EUR     |
| name          | varchar |                                 |
| exchange_rate | decimal | rate relative to base currency  |
| is_base       | boolean | only one row = true per company |

---

## Inventory Module

### products
| Column         | Type    | Notes                              |
|----------------|---------|------------------------------------|
| name           | varchar |                                    |
| sku            | varchar | unique per company                 |
| barcode        | varchar | nullable                           |
| unit_of_measure| varchar | e.g. pcs, kg, box                  |
| reorder_point  | integer | qty threshold for low-stock alert  |
| cost_price     | integer | cents (default purchase cost)      |
| selling_price  | integer | cents (default selling price)      |

### warehouses
| Column   | Type    | Notes |
|----------|---------|-------|
| name     | varchar |       |
| location | varchar | nullable |

### stock_movements
| Column          | Type    | Notes                                    |
|-----------------|---------|------------------------------------------|
| product_id      | FK      | → products                               |
| warehouse_id    | FK      | → warehouses                             |
| type            | enum    | in / out / transfer                      |
| qty             | integer |                                          |
| cost_per_unit   | integer | cents (relevant for IN movements)        |
| reference_type  | varchar | polymorphic: App\Models\Invoice, GRN etc |
| reference_id    | bigint  | polymorphic                              |
| date            | date    |                                          |

### stock_layers (FIFO cost tracking)
| Column          | Type    | Notes                                   |
|-----------------|---------|-----------------------------------------|
| product_id      | FK      | → products                              |
| warehouse_id    | FK      | → warehouses                            |
| qty_remaining   | integer | decremented as items are sold           |
| cost_per_unit   | integer | cents                                   |
| date            | date    | date of receipt (for FIFO ordering)     |

---

## Sales Module

### customers
| Column       | Type    | Notes                          |
|--------------|---------|--------------------------------|
| name         | varchar |                                |
| email        | varchar | nullable                       |
| phone        | varchar | nullable                       |
| credit_limit | integer | cents                          |
| balance      | integer | cents (outstanding AR balance) |

### quotations
| Column      | Type    | Notes                              |
|-------------|---------|------------------------------------|
| customer_id | FK      | → customers                        |
| date        | date    |                                    |
| valid_until | date    |                                    |
| status      | enum    | draft / sent / accepted / rejected |
| subtotal    | integer | cents                              |
| tax         | integer | cents                              |
| total       | integer | cents                              |
| notes       | text    | nullable                           |

### quotation_lines
| Column       | Type    | Notes           |
|--------------|---------|-----------------|
| quotation_id | FK      | → quotations    |
| product_id   | FK      | → products      |
| qty          | integer |                 |
| unit_price   | integer | cents           |
| discount     | integer | cents           |
| total        | integer | cents           |

### sales_orders
| Column         | Type    | Notes                              |
|----------------|---------|------------------------------------|
| quotation_id   | FK      | → quotations (nullable)            |
| customer_id    | FK      | → customers                        |
| date           | date    |                                    |
| status         | enum    | draft / confirmed / invoiced       |
| subtotal       | integer | cents                              |
| tax            | integer | cents                              |
| total          | integer | cents                              |

### sales_order_lines
| Column         | Type    | Notes            |
|----------------|---------|------------------|
| sales_order_id | FK      | → sales_orders   |
| product_id     | FK      | → products       |
| qty            | integer |                  |
| unit_price     | integer | cents            |
| total          | integer | cents            |

### invoices
| Column          | Type    | Notes                          |
|-----------------|---------|--------------------------------|
| sales_order_id  | FK      | → sales_orders (nullable)      |
| customer_id     | FK      | → customers                    |
| invoice_number  | varchar | unique per company, formatted  |
| date            | date    |                                |
| due_date        | date    |                                |
| status          | enum    | unpaid / partial / paid        |
| subtotal        | integer | cents                          |
| tax             | integer | cents                          |
| total           | integer | cents                          |
| paid_amount     | integer | cents                          |

### invoice_lines
| Column     | Type    | Notes         |
|------------|---------|---------------|
| invoice_id | FK      | → invoices    |
| product_id | FK      | → products    |
| qty        | integer |               |
| unit_price | integer | cents         |
| total      | integer | cents         |

### payments_received
| Column           | Type    | Notes                        |
|------------------|---------|------------------------------|
| invoice_id       | FK      | → invoices                   |
| amount           | integer | cents                        |
| date             | date    |                              |
| method           | enum    | cash / bank / card           |
| journal_entry_id | FK      | → journal_entries            |
| notes            | text    | nullable                     |

---

## Purchasing Module

### vendors
| Column  | Type    | Notes                             |
|---------|---------|-----------------------------------|
| name    | varchar |                                   |
| email   | varchar | nullable                          |
| phone   | varchar | nullable                          |
| balance | integer | cents (outstanding AP balance)    |

### purchase_orders
| Column    | Type    | Notes                               |
|-----------|---------|-------------------------------------|
| vendor_id | FK      | → vendors                           |
| date      | date    |                                     |
| status    | enum    | draft / sent / partially_received / received |
| total     | integer | cents                               |
| notes     | text    | nullable                            |

### purchase_order_lines
| Column             | Type    | Notes              |
|--------------------|---------|--------------------|
| purchase_order_id  | FK      | → purchase_orders  |
| product_id         | FK      | → products         |
| qty                | integer |                    |
| unit_cost          | integer | cents              |
| total              | integer | cents              |

### goods_received_notes
| Column             | Type    | Notes                    |
|--------------------|---------|--------------------------|
| purchase_order_id  | FK      | → purchase_orders        |
| date               | date    |                          |
| status             | enum    | draft / confirmed        |
| notes              | text    | nullable                 |

### grn_lines
| Column       | Type    | Notes                          |
|--------------|---------|--------------------------------|
| grn_id       | FK      | → goods_received_notes         |
| product_id   | FK      | → products                     |
| qty_ordered  | integer |                                |
| qty_received | integer |                                |
| unit_cost    | integer | cents                          |

### vendor_bills
| Column       | Type    | Notes                         |
|--------------|---------|-------------------------------|
| vendor_id    | FK      | → vendors                     |
| grn_id       | FK      | → goods_received_notes (nullable) |
| bill_number  | varchar | unique per company            |
| date         | date    |                               |
| due_date     | date    |                               |
| status       | enum    | unpaid / partial / paid       |
| total        | integer | cents                         |
| paid_amount  | integer | cents                         |

### payments_made
| Column           | Type    | Notes                       |
|------------------|---------|-----------------------------|
| vendor_bill_id   | FK      | → vendor_bills              |
| amount           | integer | cents                       |
| date             | date    |                             |
| method           | enum    | cash / bank / card          |
| journal_entry_id | FK      | → journal_entries           |
| notes            | text    | nullable                    |

---

## POS Module

### pos_sessions
| Column         | Type      | Notes                                   |
|----------------|-----------|-----------------------------------------|
| opened_by      | FK        | → users                                 |
| closed_by      | FK        | → users (nullable)                      |
| opened_at      | timestamp |                                         |
| closed_at      | timestamp | nullable                                |
| opening_float  | integer   | cents — cash in drawer at open          |
| expected_cash  | integer   | cents — opening_float + cash sales      |
| actual_cash    | integer   | cents — entered at close (nullable)     |
| status         | enum      | open / closed                           |

### pos_transactions
| Column              | Type      | Notes                      |
|---------------------|-----------|----------------------------|
| pos_session_id      | FK        | → pos_sessions             |
| transaction_number  | varchar   | unique, auto-generated     |
| date                | timestamp |                            |
| subtotal            | integer   | cents                      |
| tax                 | integer   | cents                      |
| total               | integer   | cents                      |
| status              | enum      | completed / voided         |

### pos_transaction_lines
| Column              | Type    | Notes                      |
|---------------------|---------|----------------------------|
| pos_transaction_id  | FK      | → pos_transactions         |
| product_id          | FK      | → products                 |
| qty                 | integer |                            |
| unit_price          | integer | cents                      |
| total               | integer | cents                      |

### pos_payments
| Column              | Type    | Notes                       |
|---------------------|---------|-----------------------------|
| pos_transaction_id  | FK      | → pos_transactions          |
| method              | enum    | cash / card                 |
| amount              | integer | cents                       |

---

## Relationships Summary

```
companies ──< all tables (company_id)

employees ──< attendance
employees ──< leave_requests >── leave_types
employees ──< payroll_items >── payroll_runs

accounts ──< journal_lines >── journal_entries

products ──< stock_movements >── warehouses
products ──< stock_layers >── warehouses

customers ──< quotations ──< quotation_lines >── products
quotations ──< sales_orders ──< sales_order_lines >── products
sales_orders ──< invoices ──< invoice_lines >── products
invoices ──< payments_received >── journal_entries

vendors ──< purchase_orders ──< purchase_order_lines >── products
purchase_orders ──< goods_received_notes ──< grn_lines >── products
vendors ──< vendor_bills ──< payments_made >── journal_entries

pos_sessions ──< pos_transactions ──< pos_transaction_lines >── products
pos_transactions ──< pos_payments
```
