<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\Currency;
use App\Modules\HR\Models\Department;
use App\Modules\HR\Models\Employee;
use App\Modules\HR\Models\LeaveType;
use App\Modules\HR\Models\Position;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Purchasing\Models\Vendor;
use App\Modules\Sales\Models\Customer;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $cid     = $company->id;

        // ── Currencies ───────────────────────────────────────────────────────
        Currency::firstOrCreate(['company_id' => $cid, 'code' => 'USD'], [
            'name' => 'US Dollar', 'exchange_rate' => 1.000000, 'is_base' => true,
        ]);

        // ── Chart of Accounts (minimal starter CoA) ──────────────────────────
        $accounts = [
            // Assets
            ['code' => '1000', 'name' => 'Assets',                 'type' => 'asset',     'parent_id' => null],
            ['code' => '1010', 'name' => 'Cash',                   'type' => 'asset',     'parent_code' => '1000'],
            ['code' => '1020', 'name' => 'Accounts Receivable',    'type' => 'asset',     'parent_code' => '1000'],
            ['code' => '1030', 'name' => 'Inventory',              'type' => 'asset',     'parent_code' => '1000'],
            // Liabilities
            ['code' => '2000', 'name' => 'Liabilities',            'type' => 'liability', 'parent_id' => null],
            ['code' => '2010', 'name' => 'Accounts Payable',       'type' => 'liability', 'parent_code' => '2000'],
            // Equity
            ['code' => '3000', 'name' => 'Equity',                 'type' => 'equity',    'parent_id' => null],
            ['code' => '3010', 'name' => 'Owner Equity',           'type' => 'equity',    'parent_code' => '3000'],
            ['code' => '3020', 'name' => 'Retained Earnings',      'type' => 'equity',    'parent_code' => '3000'],
            // Income
            ['code' => '4000', 'name' => 'Revenue',                'type' => 'income',    'parent_id' => null],
            ['code' => '4010', 'name' => 'Sales Revenue',          'type' => 'income',    'parent_code' => '4000'],
            ['code' => '4020', 'name' => 'POS Revenue',            'type' => 'income',    'parent_code' => '4000'],
            // Expenses
            ['code' => '5000', 'name' => 'Expenses',               'type' => 'expense',   'parent_id' => null],
            ['code' => '5010', 'name' => 'Cost of Goods Sold',     'type' => 'expense',   'parent_code' => '5000'],
            ['code' => '5020', 'name' => 'Salaries Expense',       'type' => 'expense',   'parent_code' => '5000'],
            ['code' => '5030', 'name' => 'Rent Expense',           'type' => 'expense',   'parent_code' => '5000'],
        ];

        $created = [];
        foreach ($accounts as $a) {
            $parentId = null;
            if (isset($a['parent_code'])) {
                $parentId = $created[$a['parent_code']]->id ?? null;
            } elseif (isset($a['parent_id'])) {
                $parentId = $a['parent_id'];
            }

            $acc = Account::firstOrCreate(
                ['company_id' => $cid, 'code' => $a['code']],
                ['name' => $a['name'], 'type' => $a['type'], 'parent_id' => $parentId, 'is_active' => true],
            );
            $created[$a['code']] = $acc;
        }

        // ── HR: Departments & Positions ──────────────────────────────────────
        $dept1 = Department::firstOrCreate(['company_id' => $cid, 'name' => 'Engineering']);
        $dept2 = Department::firstOrCreate(['company_id' => $cid, 'name' => 'Sales & Marketing']);
        $dept3 = Department::firstOrCreate(['company_id' => $cid, 'name' => 'Finance']);

        $pos1 = Position::firstOrCreate(['company_id' => $cid, 'name' => 'Software Engineer',   'department_id' => $dept1->id]);
        $pos2 = Position::firstOrCreate(['company_id' => $cid, 'name' => 'Sales Representative','department_id' => $dept2->id]);
        $pos3 = Position::firstOrCreate(['company_id' => $cid, 'name' => 'Accountant',          'department_id' => $dept3->id]);

        // ── HR: Leave Types ──────────────────────────────────────────────────
        LeaveType::firstOrCreate(['company_id' => $cid, 'name' => 'Annual Leave'],  ['days_allowed_per_year' => 21]);
        LeaveType::firstOrCreate(['company_id' => $cid, 'name' => 'Sick Leave'],    ['days_allowed_per_year' => 14]);
        LeaveType::firstOrCreate(['company_id' => $cid, 'name' => 'Unpaid Leave'],  ['days_allowed_per_year' => 30]);

        // ── HR: Sample Employees ─────────────────────────────────────────────
        Employee::firstOrCreate(
            ['company_id' => $cid, 'national_id' => 'EMP-001'],
            [
                'name'          => 'Alice Engineer',
                'department_id' => $dept1->id,
                'position_id'   => $pos1->id,
                'hire_date'     => '2023-01-15',
                'base_salary'   => 700000, // $7,000.00
                'status'        => 'active',
            ]
        );
        Employee::firstOrCreate(
            ['company_id' => $cid, 'national_id' => 'EMP-002'],
            [
                'name'          => 'Bob Sales',
                'department_id' => $dept2->id,
                'position_id'   => $pos2->id,
                'hire_date'     => '2023-03-01',
                'base_salary'   => 500000, // $5,000.00
                'status'        => 'active',
            ]
        );

        // ── Inventory: Warehouses ────────────────────────────────────────────
        $wh = Warehouse::firstOrCreate(
            ['company_id' => $cid, 'name' => 'Main Warehouse'],
            ['location' => 'Building A, Floor 1']
        );
        Warehouse::firstOrCreate(
            ['company_id' => $cid, 'name' => 'Secondary Warehouse'],
            ['location' => 'Building B, Floor 2']
        );

        // ── Inventory: Products ──────────────────────────────────────────────
        Product::firstOrCreate(
            ['company_id' => $cid, 'sku' => 'PROD-001'],
            [
                'name'            => 'Laptop Pro 15"',
                'barcode'         => '1234567890001',
                'unit_of_measure' => 'pcs',
                'reorder_point'   => 5,
                'cost_price'      => 80000,  // $800.00
                'selling_price'   => 120000, // $1,200.00
            ]
        );
        Product::firstOrCreate(
            ['company_id' => $cid, 'sku' => 'PROD-002'],
            [
                'name'            => 'Wireless Mouse',
                'barcode'         => '1234567890002',
                'unit_of_measure' => 'pcs',
                'reorder_point'   => 20,
                'cost_price'      => 1500, // $15.00
                'selling_price'   => 2999, // $29.99
            ]
        );
        Product::firstOrCreate(
            ['company_id' => $cid, 'sku' => 'PROD-003'],
            [
                'name'            => 'USB-C Hub 7-in-1',
                'barcode'         => '1234567890003',
                'unit_of_measure' => 'pcs',
                'reorder_point'   => 15,
                'cost_price'      => 2500, // $25.00
                'selling_price'   => 4999, // $49.99
            ]
        );

        // ── Sales: Customers ─────────────────────────────────────────────────
        Customer::firstOrCreate(
            ['company_id' => $cid, 'name' => 'Acme Corporation'],
            ['email' => 'billing@acme.test', 'phone' => '+1-555-0100', 'credit_limit' => 1000000, 'balance' => 0]
        );
        Customer::firstOrCreate(
            ['company_id' => $cid, 'name' => 'Global Tech Ltd'],
            ['email' => 'accounts@globaltech.test', 'phone' => '+1-555-0200', 'credit_limit' => 500000, 'balance' => 0]
        );

        // ── Purchasing: Vendors ──────────────────────────────────────────────
        Vendor::firstOrCreate(
            ['company_id' => $cid, 'name' => 'TechSupply Co.'],
            ['email' => 'orders@techsupply.test', 'phone' => '+1-555-0300', 'balance' => 0]
        );
        Vendor::firstOrCreate(
            ['company_id' => $cid, 'name' => 'Hardware Depot'],
            ['email' => 'sales@hardwaredepot.test', 'phone' => '+1-555-0400', 'balance' => 0]
        );

        $this->command->info('Sample data seeded: CoA, departments, employees, products, warehouses, customers, vendors.');
    }
}
