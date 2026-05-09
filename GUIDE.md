# GUIDE.md — ERP Deep-Dive Reasoning Document

---

## How to use this guide

This file is the *thinking layer* behind the project. `TODO.md` tells you **what** to build and in what order. `Architecture.md` shows the system structure. `database.md` defines the schema. This document tells you **why** every major decision was made, **how** each system works end-to-end through every layer, and **what to say to an AI** to generate each piece correctly. Read the relevant phase section before you open your editor. Every section ends with copy-paste-ready AI prompts that are precise enough to generate useful code on the first try — because vague prompts produce vague code. If you hit a confusing edge case, the "Common gotchas" section at the bottom is where to look first.

---

## Technology choices — the why

### Laravel 11 (Backend Framework)
- **Chosen over Node.js/Express** because financial systems require transactions, migrations, and DB integrity guarantees — Laravel's Eloquent + DB::transaction() + FormRequests give you all three with minimal ceremony.
- **Chosen over Django/FastAPI** because the PHP ecosystem for ERP tooling (DomPDF, Maatwebsite Excel, Spatie Permission) is mature and battle-tested; the Laravel community has solved every ERP sub-problem.
- **Laravel 11 specifically** eliminates the `Http/Kernel.php` and consolidates middleware registration into `bootstrap/app.php` — less boilerplate than L10.
- **Gotcha:** Laravel 11 uses a new `bootstrap/app.php` pattern. AI trained on L9/L10 will generate the old `Kernel.php` middleware registration — always verify middleware registration syntax.
- **Gotcha:** Sanctum in API-only mode (token auth, not cookie/SPA) requires `EnsureFrontendRequestsAreStateful` is NOT in the middleware stack. Add only `auth:sanctum` to route groups.

### MySQL 8 (Database)
- **Chosen over PostgreSQL** for broader shared hosting compatibility, and because MySQL 8 adds window functions, CTEs, and JSON support that close the gap for reporting queries.
- **Chosen over SQLite** for production-grade concurrency, row-level locking, and foreign key enforcement which are non-negotiable in financial systems.
- **All amounts as INT (cents)** because IEEE 754 floating point cannot represent 0.1 + 0.2 exactly. Financial rounding errors compound. `9999` never rounds wrong; `99.99` sometimes does.
- **Gotcha:** MySQL's `ONLY_FULL_GROUP_BY` mode (default in MySQL 8) will reject `SELECT name, SUM(amount) GROUP BY type` if `name` is not in GROUP BY or an aggregate. All report queries must be GROUP BY-strict.
- **Soft deletes everywhere** — financial records must never be hard-deleted. `deleted_at` gives you audit compliance and the ability to restore accidentally removed records.

### Laravel Sanctum (Authentication)
- **Chosen over JWT (tymon/jwt-auth)** because Sanctum is first-party, has no token expiry complexity to manage, stores tokens in the DB (revocable), and integrates with Laravel's `auth()` helper and policies natively.
- **Chosen over Passport** (OAuth2) because OAuth2 is massive overkill for an internal ERP with no third-party integrations in v1.
- **Token storage on frontend:** Tokens go in `localStorage` (simple, works for SPAs). The tradeoff is XSS vulnerability — mitigate with strict CSP headers and input sanitization, not by fighting the storage location.
- **Gotcha:** Sanctum's `stateful` domains config matters only for cookie-based auth (SPA mode). For API token mode, this config is irrelevant — but AI frequently generates it anyway, causing confusion.

### Spatie Laravel Permission (RBAC)
- **Chosen over hand-rolled permission tables** because Spatie is the de facto standard, handles role-permission-model pivot tables, caches permissions automatically, and provides middleware out of the box.
- **Permission-per-module approach** (e.g., `view-hr`, `manage-payroll`) is chosen over per-resource permissions because it matches how ERP roles work in reality — an HR Manager sees all HR, not just "their" employees.
- **Gotcha:** Spatie caches permissions aggressively. After seeding new permissions, run `php artisan permission:cache-reset` or calls to `$user->hasPermission()` will return stale results.
- **Gotcha:** Spatie's `permission:` middleware uses pipe syntax for OR: `permission:view-hr|view-accounting`. AI sometimes generates comma syntax which is wrong.

### Redis (Queue + Cache)
- **Chosen over database queues** (`QUEUE_CONNECTION=database`) because PDF generation and email notifications are I/O-heavy operations that would lock DB rows under load. Redis queues are non-blocking and support priority queues (`high,default`).
- **Two queue names** (`high` and `default`): `high` for user-facing async ops (payslip PDF ready notification), `default` for background tasks (stock alerts). Start workers with `--queue=high,default` so high-priority jobs always run first.
- **Gotcha:** If Redis is not running when you dispatch a job, the job silently drops in development. Always check `QUEUE_CONNECTION` in `.env` — default Laravel install sets it to `sync`, which runs jobs immediately in-process (fine for dev, wrong for PDF generation).

### Next.js 14 App Router (Frontend)
- **Chosen over plain React + Vite** because App Router gives you Server Components (run on server, zero JS sent to client), nested layouts (dashboard shell renders once, pages swap), and built-in loading/error boundaries — all critical for a data-heavy ERP.
- **Chosen over Remix** because Next.js 14 + shadcn/ui + TanStack Query is the most documented combination for building admin dashboards in 2024-2025.
- **Server Components vs Client Components:** Data-display pages (employee list, invoice list) start as Server Components (faster initial load, no hydration). Interactive pages (POS terminal, journal entry form) must be Client Components (`'use client'`).
- **App Router layout nesting** means `(dashboard)/layout.tsx` renders the sidebar once and all child pages swap inside it — no sidebar re-render on navigation.
- **Gotcha:** AI frequently generates `getServerSideProps` or `getStaticProps` — these are Pages Router APIs. In App Router, data fetching happens via `async` Server Components or TanStack Query in Client Components.

### TanStack Query v5 (Server State)
- **Chosen over SWR** because TanStack Query v5 has better mutation invalidation, optimistic updates, and infinite scroll support — all needed for POS and reporting pages.
- **Chosen over Redux/Zustand for server data** because server state (invoices, products) and UI state (is modal open?) are fundamentally different. TanStack Query owns server state; local `useState` handles UI state.
- **Gotcha:** TanStack Query v5 changed the API significantly from v4. `useQuery` now takes a single object: `useQuery({ queryKey: [...], queryFn: ... })`. AI trained on v4 generates the old two-argument form: `useQuery(['key'], fn)` — this will throw a runtime error in v5.
- **Gotcha:** `onSuccess` callback was removed in v5. Use `useEffect` watching `data` instead, or handle it inside the `queryFn`.

### Tailwind CSS + shadcn/ui (Styling)
- **shadcn/ui chosen over MUI/Ant Design** because shadcn components are copied into your codebase (not a dependency) — you own the code and can modify every pixel. MUI and Ant Design lock you into their design system.
- **Tailwind chosen over CSS Modules** because ERP UIs have hundreds of small components; utility classes at the component level are faster to write and easier to review than maintaining separate CSS files.
- **Gotcha:** shadcn/ui requires specific Tailwind config (CSS variables for theming). Run `npx shadcn@latest init` before manually adding components — it wires up the config automatically.

### Recharts (Charts)
- **Chosen over Chart.js** because Recharts is built as React components, composable, and fully controlled by props — no imperative `chart.update()` calls, no canvas refs needed.
- **Gotcha:** Recharts requires a numeric `dataKey` — if your API returns `total_cents` but you want to display as dollars, transform the data before passing to the chart, not inside the chart renderer.

---

## Phase 1 — Foundation

### Goal
Both projects install, a database connection is confirmed, and `php artisan migrate` runs without errors.

### Backend — what to understand

The Laravel project lives in `backend/`. The key Phase 1 task is wiring up the modular structure so Laravel can find classes in `app/Modules/`.

**Composer autoloading for modules:**
```json
// backend/composer.json — add to autoload.psr-4
{
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "App\\Modules\\": "app/Modules/"
    }
  }
}
```
This tells Composer that `App\Modules\HR\Models\Employee` maps to `app/Modules/HR/Models/Employee.php`. After editing, run `composer dump-autoload`.

**AppServiceProvider — binding repositories:**
```php
// backend/app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->bind(
        \App\Modules\HR\Repositories\Interfaces\EmployeeRepositoryInterface::class,
        \App\Modules\HR\Repositories\EmployeeRepository::class
    );
    // repeat for every interface → implementation pair
}
```
This is the Dependency Injection (DI) container registration. When a Service class type-hints `EmployeeRepositoryInterface` in its constructor, Laravel automatically resolves it to `EmployeeRepository`. The *why*: your Service code depends on the interface (stable contract), not the concrete class — making it testable by swapping in a fake repository.

**Migration order matters** (see the dedicated section below). The `database/migrations/` directory uses timestamps in filenames; Laravel runs them alphabetically by timestamp. Name them explicitly:
```
2024_01_01_000001_create_companies_table.php
2024_01_01_000002_create_users_table.php
...
```

**BaseRepository pattern:**
```php
// Every module repository extends this
abstract class BaseRepository
{
    public function __construct(protected Model $model) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }
}

// Module repository
class EmployeeRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Employee());
    }

    // Only module-specific queries live here
    public function findByDepartment(int $deptId): Collection
    {
        return $this->model->where('department_id', $deptId)->get();
    }
}
```
The base class handles the generic CRUD that every module needs. Module repositories only add queries specific to that model. This prevents duplication and keeps repository methods focused.

**company_id scoping** — use a Global Scope so you never forget it:
```php
// app/Models/Scopes/CompanyScope.php
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->getTable() . '.company_id', auth()->user()?->company_id);
    }
}

// In every model's boot():
protected static function booted(): void
{
    static::addGlobalScope(new CompanyScope());
}
```
Without this, a bug in any query could leak another company's data. The Global Scope is automatic — you can't forget to add `where('company_id', ...)` because the model adds it for you.

### Frontend — what to understand

**App Router root layout** wraps every page with providers:
```tsx
// frontend/app/layout.tsx
export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>
        <QueryProvider>
          <AuthProvider>
            {children}
          </AuthProvider>
        </QueryProvider>
      </body>
    </html>
  );
}
```
`QueryProvider` must wrap `AuthProvider` because `AuthProvider` uses TanStack Query internally to fetch `/auth/me`. Order matters.

**Environment variable naming:** Next.js only exposes env vars prefixed with `NEXT_PUBLIC_` to the browser. Server-side vars (no prefix) are only available in Server Components and API routes.
```
NEXT_PUBLIC_API_URL=http://localhost:8000/api   # browser-accessible
```

### AI prompting patterns for this phase

**Prompt 1 — Migrations:**
```
Generate a Laravel 11 migration file for the `employees` table.
File: backend/database/migrations/2024_01_01_000010_create_employees_table.php
Columns: id (bigint PK), company_id (FK→companies, cascade delete), user_id (FK→users nullable),
name varchar(255), national_id varchar(100) unique-per-company, department_id (FK→departments),
position_id (FK→positions), hire_date date, base_salary int unsigned (store cents),
status enum('active','inactive','terminated') default active,
created_at, updated_at, deleted_at (softDeletes).
Add a unique index on [company_id, national_id]. Use the Blueprint fluent API only.
```

**Prompt 2 — AppServiceProvider bindings:**
```
In backend/app/Providers/AppServiceProvider.php, add repository bindings for all 8 modules.
Each module has an interface at App\Modules\{Module}\Repositories\Interfaces\{Model}RepositoryInterface
and an implementation at App\Modules\{Module}\Repositories\{Model}Repository.
Modules: HR (Employee, Attendance), Accounting (Account, JournalEntry), Inventory (Product, StockMovement),
Sales (Customer, Invoice), Purchasing (Vendor, VendorBill), POS (PosSession, PosTransaction).
Use $this->app->bind(Interface::class, Implementation::class) inside the register() method.
```

**Prompt 3 — Next.js layout:**
```
Create frontend/app/(dashboard)/layout.tsx using Next.js 14 App Router.
This layout: (1) checks if user is authenticated via useAuth() hook — if not, redirects to /login using next/navigation useRouter,
(2) renders a two-column layout: <Sidebar /> on the left (fixed, 240px wide), main content area on the right with overflow-y-auto,
(3) the Sidebar reads permissions from usePermissions() and renders nav items conditionally.
Mark this as 'use client'. Use Tailwind for layout. Import Sidebar from '@/components/layout/Sidebar'.
```

---

## Phase 2 — Auth & RBAC

### Goal
A developer can POST to `/api/auth/login` with email/password, receive a Sanctum token, and access `/api/auth/me` returning the user with their roles and permissions array.

### Backend — what to understand

**AuthController — the only thin controller that has slightly more logic:**
```php
class AuthController extends BaseController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user'  => new UserResource($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()->load('roles', 'permissions')));
    }
}
```
`ValidationException::withMessages()` returns HTTP 422 with the standard Laravel error format — same shape as FormRequest validation errors. Frontend only needs to handle one error format.

**UserResource — flatten permissions for the frontend:**
```php
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'roles'       => $this->getRoleNames(),           // ['HR Manager']
            'permissions' => $this->getAllPermissions()        // ['view-hr', 'manage-employees']
                                  ->pluck('name'),
        ];
    }
}
```
The frontend receives a flat `permissions: string[]` array. `getAllPermissions()` from Spatie returns both direct permissions AND permissions inherited from roles — so the frontend only needs to check one array.

**Seeder strategy:**
```php
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view-hr', 'manage-employees', 'manage-payroll',
            'view-accounting', 'manage-journals', 'view-reports',
            'view-inventory', 'manage-products', 'manage-stock',
            'view-sales', 'manage-invoices', 'manage-customers',
            'view-purchasing', 'manage-vendors',
            'use-pos', 'manage-pos-sessions',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'sanctum']);
        }

        $roles = [
            'Super Admin'  => $permissions,
            'HR Manager'   => ['view-hr', 'manage-employees', 'manage-payroll'],
            'Accountant'   => ['view-accounting', 'manage-journals', 'view-reports'],
            'Warehouse'    => ['view-inventory', 'manage-stock'],
            'Sales Rep'    => ['view-sales', 'manage-invoices', 'manage-customers', 'use-pos'],
            'Purchasing'   => ['view-purchasing', 'manage-vendors', 'view-inventory'],
            'Viewer'       => ['view-reports'],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);
            $role->syncPermissions($perms);
        }
    }
}
```
`firstOrCreate` makes the seeder idempotent — safe to run multiple times. `syncPermissions` replaces the role's permissions atomically. **Critical:** the `guard_name` must be `'sanctum'`, not `'web'` — Spatie ties permissions to guards, and your API uses the `sanctum` guard.

### Frontend — what to understand

**Protected route pattern using layout redirect:**
```tsx
// app/(dashboard)/layout.tsx — simplified
'use client';
import { useAuth } from '@/lib/hooks/useAuth';
import { useRouter } from 'next/navigation';
import { useEffect } from 'react';

export default function DashboardLayout({ children }) {
  const { user, loading } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && !user) router.replace('/login');
  }, [loading, user, router]);

  if (loading) return <FullPageSpinner />;
  if (!user) return null; // prevents flash before redirect
  return <div className="flex h-screen">{/* sidebar + content */}</div>;
}
```
The `loading` state prevents a flash of the login redirect while the initial `/me` request is in-flight. `router.replace` (not `push`) so the user can't hit Back to reach the dashboard after logout.

**Sidebar permission filtering:**
```tsx
const navItems = [
  { label: 'HR',         href: '/hr/employees',    permission: 'view-hr' },
  { label: 'Accounting', href: '/accounting',       permission: 'view-accounting' },
  { label: 'Inventory',  href: '/inventory/products', permission: 'view-inventory' },
  { label: 'Sales',      href: '/sales/customers',  permission: 'view-sales' },
  { label: 'Purchasing', href: '/purchasing/vendors', permission: 'view-purchasing' },
  { label: 'POS',        href: '/pos',              permission: 'use-pos' },
  { label: 'Reports',    href: '/reports',          permission: 'view-reports' },
];

// In Sidebar component:
const { hasPermission } = usePermissions();
const visible = navItems.filter(item => hasPermission(item.permission));
```
The sidebar is declarative — add a nav item to the array and the permission check is automatic. Never hard-code role names in the UI — always check permissions (a Super Admin has all permissions, so `hasPermission()` returns true for them via the `roles.includes('Super Admin')` short-circuit).

### AI prompting patterns for this phase

**Prompt 1 — Login page:**
```
Create frontend/app/(auth)/login/page.tsx using Next.js 14 App Router.
This is a 'use client' component. It renders a centered login card using shadcn/ui Card, CardContent.
Form fields: email (Input), password (Input type="password"), Login button (Button).
On submit: call useAuth().login({ email, password }), handle errors by showing the error message below the form,
on success redirect to '/' using useRouter().push('/').
Show a loading spinner inside the Button while submitting.
Use react-hook-form for form state with zod validation (email must be valid email, password min 6 chars).
Import useAuth from '@/lib/hooks/useAuth'.
```

**Prompt 2 — Permission seeder:**
```
Generate backend/database/seeders/RolePermissionSeeder.php for Laravel 11 with Spatie Laravel Permission.
Use guard_name 'sanctum' for all roles and permissions.
Permissions to create (16 total): view-hr, manage-employees, manage-payroll,
view-accounting, manage-journals, view-reports, view-inventory, manage-products,
manage-stock, view-sales, manage-invoices, manage-customers, view-purchasing,
manage-vendors, use-pos, manage-pos-sessions.
Roles and their permission sets:
- Super Admin: all 16
- HR Manager: view-hr, manage-employees, manage-payroll
- Accountant: view-accounting, manage-journals, view-reports
- Warehouse: view-inventory, manage-stock
- Sales Rep: view-sales, manage-invoices, manage-customers, use-pos
- Purchasing: view-purchasing, manage-vendors, view-inventory
- Viewer: view-reports
Use firstOrCreate for idempotency. Use syncPermissions on each role.
```

**Prompt 3 — UserResource:**
```
Generate backend/app/Modules/Auth/Resources/UserResource.php.
Extend Illuminate\Http\Resources\Json\JsonResource.
toArray() returns: id, name, email, is_active, company_id,
roles as array of role name strings (use $this->getRoleNames()),
permissions as array of permission name strings (use $this->getAllPermissions()->pluck('name')).
No timestamps in the response.
```

---

## Phase 3 — HR Module

### Goal
Full CRUD for departments, positions, and employees is working; attendance clock-in/out logs correctly; leave requests flow from pending to approved/rejected; payroll runs calculate net pay and dispatch a PDF job.

### Backend — what to understand

**Payroll calculation — the Service layer pattern:**
```php
// app/Modules/HR/Services/PayrollService.php
class PayrollService extends BaseService
{
    public function __construct(
        private EmployeeRepository $employees,
        private PayrollRunRepository $runs,
        private PayrollItemRepository $items,
    ) {}

    public function runPayroll(int $companyId, int $month, int $year): PayrollRun
    {
        return DB::transaction(function () use ($companyId, $month, $year) {
            $run = $this->runs->create([
                'company_id' => $companyId,
                'month'      => $month,
                'year'       => $year,
                'status'     => 'draft',
            ]);

            $employees = $this->employees->activeForCompany($companyId);

            foreach ($employees as $employee) {
                // Base salary earning
                $this->items->create([
                    'payroll_run_id' => $run->id,
                    'employee_id'    => $employee->id,
                    'type'           => 'earning',
                    'description'    => 'Base Salary',
                    'amount'         => $employee->base_salary, // already in cents
                ]);

                // You'd also add allowances and deductions here from
                // a configurable compensation_components table
            }

            return $run;
        });
    }

    public function calculateNetPay(int $payrollRunId, int $employeeId): int
    {
        $items = $this->items->forEmployee($payrollRunId, $employeeId);
        $earnings   = $items->where('type', 'earning')->sum('amount');
        $deductions = $items->where('type', 'deduction')->sum('amount');
        return $earnings - $deductions; // result in cents
    }
}
```
The Service calls Repositories, never Eloquent directly. `DB::transaction()` wraps the entire payroll run — if any employee's items fail to insert, nothing is committed. The net pay calculation is a simple sum — no magic formulas in the code, because formula components live in the DB.

**Leave approval — state machine:**
```php
// app/Modules/HR/Models/LeaveRequest.php
class LeaveRequest extends Model
{
    const ALLOWED_TRANSITIONS = [
        'pending'  => ['approved', 'rejected'],
        'approved' => [],
        'rejected' => [],
    ];

    public function transitionTo(string $newStatus): void
    {
        $allowed = self::ALLOWED_TRANSITIONS[$this->status] ?? [];

        if (!in_array($newStatus, $allowed)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$this->status} to {$newStatus}"
            );
        }

        $this->update(['status' => $newStatus]);
    }
}

// In HrService:
public function approveLeave(int $leaveId, int $approverId): LeaveRequest
{
    $leave = $this->leaves->findOrFail($leaveId);
    $leave->transitionTo('approved');
    $leave->update(['approved_by' => $approverId]);
    return $leave;
}
```
The state machine lives on the Model — not the Controller, not the Service. The Model owns its own invariants. `transitionTo()` throws an exception for illegal transitions, which Laravel catches and returns as a 500 (add a `Handler.php` entry to return 422 for this exception type).

**Queued payslip PDF:**
```php
// app/Modules/HR/Jobs/GeneratePayslipJob.php
class GeneratePayslipJob implements ShouldQueue
{
    public $queue = 'high';

    public function __construct(
        public readonly int $payrollRunId,
        public readonly int $employeeId
    ) {}

    public function handle(): void
    {
        $data = app(PayrollService::class)->getPayslipData($this->payrollRunId, $this->employeeId);
        $pdf  = Pdf::loadView('pdf.payslip', $data);
        $path = "payslips/{$this->payrollRunId}/{$this->employeeId}.pdf";
        Storage::put($path, $pdf->output());
        // Optionally: notify employee via event/mail
    }
}

// Dispatched from PayrollController:
GeneratePayslipJob::dispatch($run->id, $employee->id);
```
The job runs in the `high` queue — users waiting for their payslip PDF should get it fast. Never generate PDFs synchronously in an HTTP request; DomPDF rendering a complex payslip takes 1-3 seconds.

### Frontend — what to understand

**TanStack Query for list + mutation pattern (the standard you'll repeat everywhere):**
```tsx
// components/hr/EmployeeList.tsx
'use client';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { hrApi } from '@/lib/api/hr';

export function EmployeeList() {
  const queryClient = useQueryClient();

  // Fetch
  const { data, isLoading } = useQuery({
    queryKey: ['employees'],
    queryFn: () => hrApi.getEmployees().then(r => r.data.data),
  });

  // Mutate
  const deleteEmployee = useMutation({
    mutationFn: (id: number) => hrApi.deleteEmployee(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['employees'] });
    },
  });

  if (isLoading) return <TableSkeleton />;

  return (
    <DataTable
      data={data ?? []}
      onDelete={(id) => deleteEmployee.mutate(id)}
    />
  );
}
```
`queryKey: ['employees']` is the cache key. After a delete mutation succeeds, `invalidateQueries` marks the cache stale and triggers a background refetch — the table updates automatically. This is the pattern for 90% of ERP screens.

### AI prompting patterns for this phase

**Prompt 1 — Employee API module:**
```
Generate backend/app/Modules/HR/Services/HrService.php.
This service handles: createEmployee(array $data), updateEmployee(Employee $employee, array $data),
terminateEmployee(Employee $employee).
Inject EmployeeRepository via constructor.
createEmployee: creates employee row, if $data['user_id'] is provided ensure that user exists.
terminateEmployee: sets status='terminated', does NOT hard delete.
All methods should be wrapped in DB::transaction() where multiple writes occur.
Return the Employee model (fresh() after updates). No direct Eloquent calls — use $this->employeeRepo.
Namespace: App\Modules\HR\Services.
```

**Prompt 2 — Leave request form:**
```
Create frontend/app/(dashboard)/hr/leaves/page.tsx (Next.js 14, 'use client').
Fetch leave requests from GET /api/hr/leaves using TanStack Query v5 (useQuery with queryKey ['leaves']).
Display in a shadcn/ui Table with columns: Employee Name, Leave Type, From, To, Status (Badge colored by status), Actions.
Actions column: approve button (green) and reject button (red) — only show if status is 'pending'
and usePermissions().hasPermission('manage-employees') is true.
Approve/reject call PUT /api/hr/leaves/{id}/approve and /reject via useMutation.
On success, invalidate queryKey ['leaves']. Show toast on success/error using shadcn/ui useToast.
```

**Prompt 3 — Payslip Blade view for PDF:**
```
Create backend/resources/views/pdf/payslip.blade.php for DomPDF rendering.
Variables available: $employee (name, national_id, department, position), $run (month, year),
$items (collection of payroll_items with type, description, amount in cents),
$netPay (integer cents), $company (name, address).
Layout: company header, employee info block, table of earnings/deductions (formatted with number_format($amount/100, 2)),
net pay total at bottom. Use inline CSS only (DomPDF does not support external stylesheets or Flexbox — use tables for layout).
```

---

## Phase 4 — Accounting Foundation

### Goal
A journal entry can be created with debit/credit lines, the system rejects unbalanced entries, and the entry is posted to the ledger permanently.

### Backend — what to understand

**AccountingService — the most critical service in the entire system:**
```php
class AccountingService extends BaseService
{
    public function createEntry(array $lines, string $type, string $reference, string $description, string $date = null): JournalEntry
    {
        $this->validateBalance($lines);

        return DB::transaction(function () use ($lines, $type, $reference, $description, $date) {
            $entry = $this->entryRepo->create([
                'company_id'  => auth()->user()->company_id,
                'date'        => $date ?? now()->toDateString(),
                'reference'   => $reference,
                'description' => $description,
                'type'        => $type,
                'status'      => 'posted', // auto-generated entries post immediately
            ]);

            foreach ($lines as $line) {
                $this->lineRepo->create([
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $line['account_id'],
                    'debit'            => $line['debit'] ?? 0,
                    'credit'           => $line['credit'] ?? 0,
                    'description'      => $line['description'] ?? null,
                ]);
            }

            return $entry->load('lines.account');
        });
    }

    private function validateBalance(array $lines): void
    {
        $totalDebits  = array_sum(array_column($lines, 'debit'));
        $totalCredits = array_sum(array_column($lines, 'credit'));

        if ($totalDebits !== $totalCredits) {
            throw new UnbalancedJournalException(
                "Journal entry is unbalanced: debits={$totalDebits}, credits={$totalCredits}"
            );
        }
    }
}
```
`validateBalance` uses `!==` (strict), not `!=`, because both values are integers. If they were floats, this comparison would be unreliable — another reason money must be integers. `UnbalancedJournalException` is a custom exception registered in `bootstrap/app.php` to return HTTP 422.

**Why manual journal entries use 'draft' status:**
Auto-generated entries (from sales, purchases, POS) post immediately because the system knows they're correct. Manual entries created by an accountant go to `draft` first, requiring an explicit `POST /journal-entries/{id}/post` action. This mirrors real accounting workflows and prevents accidental ledger corruption.

**Chart of Accounts — the parent-child hierarchy:**
```php
// Account model with self-referential relationship
class Account extends Model
{
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    // Standard account number ranges:
    // 1000-1999: Assets
    // 2000-2999: Liabilities
    // 3000-3999: Equity
    // 4000-4999: Income / Revenue
    // 5000-5999: Expenses (COGS, operating)
}
```
Parent accounts (e.g., "1000 — Current Assets") are grouping nodes. Leaf accounts (e.g., "1010 — Cash", "1020 — Accounts Receivable") are posting accounts — only leaf accounts should have journal lines. Enforce this in `StoreAccountRequest` by checking if `parent_id` targets a leaf account.

### Frontend — what to understand

**Journal entry form — dynamic line items:**
```tsx
// The form uses useFieldArray from react-hook-form
const { fields, append, remove } = useFieldArray({ control, name: 'lines' });

// Real-time balance indicator
const lines = watch('lines') ?? [];
const totalDebits  = lines.reduce((sum, l) => sum + toCents(l.debit  || 0), 0);
const totalCredits = lines.reduce((sum, l) => sum + toCents(l.credit || 0), 0);
const isBalanced   = totalDebits > 0 && totalDebits === totalCredits;
```
The balance indicator shows green/red in real time before submission. The submit button is disabled unless `isBalanced` is true — catching errors before the API round-trip.

### AI prompting patterns for this phase

**Prompt 1 — AccountingService:**
```
Generate backend/app/Modules/Accounting/Services/AccountingService.php.
Namespace: App\Modules\Accounting\Services.
Inject: JournalEntryRepository, JournalLineRepository, AccountRepository via constructor.
Methods:
1. createEntry(array $lines, string $type, string $reference, string $description, ?string $date = null): JournalEntry
   - Validate that sum of all $line['debit'] === sum of all $line['credit'] (both integers, strict comparison)
   - If unbalanced, throw App\Exceptions\UnbalancedJournalException with message showing amounts
   - Wrap creation in DB::transaction()
   - Create journal_entry row, then create one journal_line per element in $lines
   - Return the entry with lines and accounts eager-loaded
2. postEntry(JournalEntry $entry): JournalEntry
   - If entry->status !== 'draft', throw InvalidArgumentException
   - Update status to 'posted', return fresh model
```

**Prompt 2 — Account tree frontend:**
```
Create frontend/app/(dashboard)/accounting/chart-of-accounts/page.tsx.
Fetch GET /api/accounting/accounts (returns flat list with parent_id).
Transform flat list into a tree structure using a buildTree() function that takes Account[] and returns nested Account[] where each account has a children array.
Render as an expandable/collapsible tree using shadcn/ui Collapsible.
Each row shows: code, name, type (Badge), and an Edit button.
Types should have colors: asset=blue, liability=red, equity=purple, income=green, expense=orange.
Indent child accounts by level (use recursive rendering).
Mark this as 'use client'. Use TanStack Query v5 useQuery with queryKey ['accounts'].
```

---

## Phase 5 — Inventory Module

### Goal
Products and warehouses exist; stock can be added (IN), removed (OUT), and transferred; current stock level is computable per product per warehouse; FIFO cost layers are maintained.

### Backend — what to understand

**StockService — the most-called service across modules:**
```php
class StockService extends BaseService
{
    public function addStock(int $productId, int $warehouseId, int $qty, int $costPerUnit, Model $reference): StockMovement
    {
        return DB::transaction(function () use ($productId, $warehouseId, $qty, $costPerUnit, $reference) {
            $movement = $this->movementRepo->create([
                'company_id'     => auth()->user()->company_id,
                'product_id'     => $productId,
                'warehouse_id'   => $warehouseId,
                'type'           => 'in',
                'qty'            => $qty,
                'cost_per_unit'  => $costPerUnit,
                'reference_type' => get_class($reference),
                'reference_id'   => $reference->id,
                'date'           => now()->toDateString(),
            ]);

            // Create FIFO layer
            $this->layerRepo->create([
                'product_id'    => $productId,
                'warehouse_id'  => $warehouseId,
                'qty_remaining' => $qty,
                'cost_per_unit' => $costPerUnit,
                'date'          => now()->toDateString(),
            ]);

            return $movement;
        });
    }

    public function deductStock(int $productId, int $warehouseId, int $qty, Model $reference): int
    {
        // Returns total COGS for this deduction (in cents)
        $available = $this->getCurrentStock($productId, $warehouseId);

        if ($available < $qty) {
            throw new InsufficientStockException(
                "Only {$available} units available, {$qty} requested."
            );
        }

        return DB::transaction(function () use ($productId, $warehouseId, $qty, $reference) {
            $this->movementRepo->create([/* type: 'out', qty: $qty, ... */]);

            // FIFO: consume oldest layers first
            $cogs       = 0;
            $remaining  = $qty;
            $layers     = $this->layerRepo->oldestFirst($productId, $warehouseId);

            foreach ($layers as $layer) {
                if ($remaining <= 0) break;
                $consume       = min($remaining, $layer->qty_remaining);
                $cogs         += $consume * $layer->cost_per_unit;
                $remaining    -= $consume;
                $layer->decrement('qty_remaining', $consume);
            }

            CheckLowStockJob::dispatch($productId, $warehouseId)->onQueue('default');

            return $cogs;
        });
    }

    public function getCurrentStock(int $productId, int $warehouseId): int
    {
        return $this->movementRepo->calculateStock($productId, $warehouseId);
    }
}
```

**Why COGS is computed from FIFO layers, not from product.cost_price:**
`product.cost_price` is the *default* purchase cost. But actual inventory consists of batches bought at different prices over time. When you sell a product bought in January at $10 and February at $12, the COGS for the January units is $10, not the current default cost. FIFO layers track this accurately.

**Stock level query — never a column:**
```php
// StockMovementRepository
public function calculateStock(int $productId, int $warehouseId): int
{
    return (int) DB::table('stock_movements')
        ->where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->whereNull('deleted_at')
        ->selectRaw("SUM(CASE WHEN type IN ('in') THEN qty ELSE -qty END) as total")
        ->value('total');
}
```
This is the "immutable ledger" pattern. `deleted_at` check matters: if a movement is soft-deleted (e.g., a voided POS transaction), it should not affect stock levels.

### AI prompting patterns for this phase

**Prompt 1 — FIFO deduction:**
```
Generate the deductStock method for backend/app/Modules/Inventory/Services/StockService.php.
Method signature: public function deductStock(int $productId, int $warehouseId, int $qty, Model $reference): int
Logic:
1. Check current stock via StockMovementRepository::calculateStock() — throw InsufficientStockException if qty > available
2. Create a stock_movements row (type='out', qty=$qty, company_id from auth user, date=today, reference_type/id from $reference)
3. Consume stock_layers FIFO: query layers where product_id=$productId AND warehouse_id=$warehouseId AND qty_remaining > 0, ordered by date ASC, id ASC
4. Loop: consume min($remaining, $layer->qty_remaining) from each layer, accumulate COGS (consume * layer->cost_per_unit)
5. Dispatch CheckLowStockJob::dispatch($productId, $warehouseId)->onQueue('default')
6. Return total COGS as integer (cents)
All writes inside DB::transaction(). Inject StockMovementRepository and StockLayerRepository.
```

---

## Phase 6 — Sales Module

### Goal
The full quotation → order → invoice pipeline works; confirming an invoice deducts stock and creates a balanced journal entry; recording a payment updates the invoice paid_amount and creates an AR payment entry.

### Backend — what to understand

**SalesService — cross-module orchestration:**
```php
class SalesService extends BaseService
{
    public function __construct(
        private InvoiceRepository $invoices,
        private StockService $stockService,           // injected from Inventory module
        private AccountingService $accountingService, // injected from Accounting module
    ) {}

    public function confirmInvoice(Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'unpaid') {
            throw new \InvalidArgumentException('Invoice already confirmed.');
        }

        return DB::transaction(function () use ($invoice) {
            $totalCogs = 0;

            foreach ($invoice->lines as $line) {
                $cogs       = $this->stockService->deductStock(
                    $line->product_id,
                    config('erp.default_warehouse_id'), // or per-line warehouse
                    $line->qty,
                    $invoice
                );
                $totalCogs += $cogs;
            }

            // DR Accounts Receivable / CR Revenue + Tax Payable
            // DR Cost of Goods Sold / CR Inventory
            $this->accountingService->createEntry([
                ['account_id' => config('accounts.ar'),          'debit'  => $invoice->total,    'credit' => 0],
                ['account_id' => config('accounts.revenue'),     'debit'  => 0, 'credit' => $invoice->subtotal],
                ['account_id' => config('accounts.tax_payable'), 'debit'  => 0, 'credit' => $invoice->tax],
                ['account_id' => config('accounts.cogs'),        'debit'  => $totalCogs,         'credit' => 0],
                ['account_id' => config('accounts.inventory'),   'debit'  => 0, 'credit' => $totalCogs],
            ], 'sale', $invoice->invoice_number, "Invoice {$invoice->invoice_number}");

            return $invoice; // status changes to 'unpaid' (it was draft before)
        });
    }

    public function recordPayment(Invoice $invoice, int $amount, string $method, string $date): PaymentReceived
    {
        return DB::transaction(function () use ($invoice, $amount, $method, $date) {
            $payment = PaymentReceived::create([
                'invoice_id' => $invoice->id,
                'amount'     => $amount,
                'method'     => $method,
                'date'       => $date,
            ]);

            $invoice->increment('paid_amount', $amount);
            $newPaidAmount = $invoice->fresh()->paid_amount;

            $invoice->update([
                'status' => $newPaidAmount >= $invoice->total ? 'paid' : 'partial',
            ]);

            $journalEntry = $this->accountingService->createEntry([
                ['account_id' => config('accounts.cash'),  'debit'  => $amount, 'credit' => 0],
                ['account_id' => config('accounts.ar'),    'debit'  => 0, 'credit' => $amount],
            ], 'payment', "PMT-{$payment->id}", "Payment for invoice {$invoice->invoice_number}", $date);

            $payment->update(['journal_entry_id' => $journalEntry->id]);

            return $payment;
        });
    }
}
```
**Why account IDs live in config, not hardcoded:** Account IDs vary per company (each company has its own chart of accounts). Storing them in `config/accounts.php` (which reads from the company's settings or from a seed) keeps the Service code generic. An alternative is a `system_accounts` table with semantic keys like `'accounts_receivable'`.

### AI prompting patterns for this phase

**Prompt 1 — Invoice confirmation:**
```
Generate the confirmInvoice method for backend/app/Modules/Sales/Services/SalesService.php.
Inject: InvoiceRepository, StockService (App\Modules\Inventory\Services\StockService),
AccountingService (App\Modules\Accounting\Services\AccountingService).
confirmInvoice(Invoice $invoice): Invoice
1. Throw InvalidArgumentException if invoice->status is not 'draft'
2. Inside DB::transaction():
   a. For each invoice line: call $this->stockService->deductStock(product_id, warehouse_id=1, qty, $invoice) and accumulate $totalCogs
   b. Call $this->accountingService->createEntry() with 5 lines:
      - DR Accounts Receivable (account_id from config('erp.accounts.ar')): debit=$invoice->total
      - CR Revenue (config('erp.accounts.revenue')): credit=$invoice->subtotal
      - CR Tax Payable (config('erp.accounts.tax_payable')): credit=$invoice->tax
      - DR COGS (config('erp.accounts.cogs')): debit=$totalCogs
      - CR Inventory (config('erp.accounts.inventory')): credit=$totalCogs
      Type='sale', reference=$invoice->invoice_number
   c. Update invoice status to 'unpaid'
3. Return fresh invoice with lines loaded
```

---

## Phase 7 — Purchasing Module

### Goal
A confirmed GRN creates a stock IN movement, a vendor bill, and an AP journal entry — all in one atomic transaction.

### Backend — what to understand

**Three-way matching pattern:**
The GRN links the PO (what was ordered) to the bill (what was charged). When confirming a GRN, the system creates the vendor bill automatically, using the GRN's received quantities and unit costs — not the PO's ordered quantities (partial deliveries are normal).

```php
public function confirmGrn(GoodsReceivedNote $grn): GoodsReceivedNote
{
    return DB::transaction(function () use ($grn) {
        $billTotal = 0;

        foreach ($grn->lines as $line) {
            $this->stockService->addStock(
                $line->product_id,
                config('erp.default_warehouse_id'),
                $line->qty_received,
                $line->unit_cost,
                $grn
            );
            $billTotal += $line->qty_received * $line->unit_cost;
        }

        $bill = VendorBill::create([
            'vendor_id'   => $grn->purchaseOrder->vendor_id,
            'grn_id'      => $grn->id,
            'bill_number' => $this->generateBillNumber(),
            'date'        => now()->toDateString(),
            'due_date'    => now()->addDays(30)->toDateString(),
            'total'       => $billTotal,
            'paid_amount' => 0,
            'status'      => 'unpaid',
        ]);

        $this->accountingService->createEntry([
            ['account_id' => config('erp.accounts.inventory'), 'debit'  => $billTotal, 'credit' => 0],
            ['account_id' => config('erp.accounts.ap'),        'debit'  => 0, 'credit' => $billTotal],
        ], 'purchase', $bill->bill_number, "GRN #{$grn->id} received");

        $grn->update(['status' => 'confirmed']);
        return $grn;
    });
}
```

---

## Phase 8 — POS Module

### Goal
A cashier can open a session, complete a sale (product scan → cart → payment), and close the session with a reconciliation report showing expected vs actual cash.

### Backend — what to understand

**Session management — one open session per company:**
```php
public function openSession(int $companyId, int $userId, int $openingFloat): PosSession
{
    $existing = PosSession::where('company_id', $companyId)
                          ->where('status', 'open')
                          ->first();

    if ($existing) {
        throw new \InvalidArgumentException('A session is already open. Close it first.');
    }

    return PosSession::create([
        'company_id'    => $companyId,
        'opened_by'     => $userId,
        'opened_at'     => now(),
        'opening_float' => $openingFloat,
        'expected_cash' => $openingFloat,
        'status'        => 'open',
    ]);
}
```

**Idempotency with transaction numbers:**
```php
// In PosTransactionController::store()
$existing = PosTransaction::where('transaction_number', $request->transaction_number)->first();
if ($existing) {
    return $this->success(new PosTransactionResource($existing)); // return the existing one
}
// ...proceed to create
```
POS networks are unreliable. A cashier might tap "Complete Sale" twice. The client generates a UUID `transaction_number` and sends it with the request. If the server sees the same number twice, it returns the first result — the second call is a no-op. This prevents duplicate sales.

### Frontend — what to understand

**POS terminal is the most UI-intensive screen — Client Component entirely:**
```
State that lives locally (useState):
- cart: CartItem[]
- selectedPayments: PosPayment[]
- searchQuery: string

State that lives in TanStack Query cache:
- products (from GET /api/inventory/products — prefetched)
- currentSession (from GET /api/pos/sessions/current)

Data flow:
1. User scans barcode → keypress event → filter products by barcode → add to cart
2. User clicks Pay → split payment UI → total validation
3. Mutate: POST /api/pos/transactions → on success: clear cart, print receipt, update session cash display
```

**Barcode scan via keyboard input:**
```tsx
// Barcode scanners emulate keyboard input, ending with Enter
useEffect(() => {
  let buffer = '';
  const handler = (e: KeyboardEvent) => {
    if (e.key === 'Enter' && buffer.length > 3) {
      addToCartByBarcode(buffer);
      buffer = '';
    } else if (e.key.length === 1) {
      buffer += e.key;
    }
    // Reset buffer after 100ms of no input (prevents partial reads)
    clearTimeout(timer);
    timer = setTimeout(() => { buffer = ''; }, 100);
  };
  window.addEventListener('keydown', handler);
  return () => window.removeEventListener('keydown', handler);
}, []);
```

---

## Phase 9 — Financial Reports

### Goal
Trial Balance, Income Statement, and Balance Sheet generate correctly from live journal data; all three export to PDF and Excel.

### Backend — what to understand

**Trial Balance query:**
```php
public function getTrialBalance(string $startDate, string $endDate): Collection
{
    return DB::table('accounts')
        ->leftJoin('journal_lines', 'accounts.id', '=', 'journal_lines.account_id')
        ->leftJoin('journal_entries', function ($join) use ($startDate, $endDate) {
            $join->on('journal_entries.id', '=', 'journal_lines.journal_entry_id')
                 ->where('journal_entries.status', 'posted')
                 ->whereBetween('journal_entries.date', [$startDate, $endDate]);
        })
        ->where('accounts.company_id', auth()->user()->company_id)
        ->select(
            'accounts.code',
            'accounts.name',
            'accounts.type',
            DB::raw('COALESCE(SUM(journal_lines.debit), 0) as total_debit'),
            DB::raw('COALESCE(SUM(journal_lines.credit), 0) as total_credit'),
            DB::raw('COALESCE(SUM(journal_lines.debit), 0) - COALESCE(SUM(journal_lines.credit), 0) as balance'),
        )
        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
        ->orderBy('accounts.code')
        ->get();
}
```
`COALESCE(..., 0)` handles accounts with no transactions in the period — they appear with zero balances rather than NULL. The `LEFT JOIN` ensures all accounts appear even if they have no journal lines in the date range.

**Excel export pattern:**
```php
// app/Modules/Reports/Exports/TrialBalanceExport.php
class TrialBalanceExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $data) {}

    public function collection(): Collection { return $this->data; }

    public function headings(): array
    {
        return ['Code', 'Account Name', 'Type', 'Total Debit', 'Total Credit', 'Balance'];
    }

    public function map($row): array
    {
        return [
            $row->code,
            $row->name,
            $row->type,
            number_format($row->total_debit / 100, 2),
            number_format($row->total_credit / 100, 2),
            number_format($row->balance / 100, 2),
        ];
    }
}

// Controller:
return Excel::download(new TrialBalanceExport($data), 'trial-balance.xlsx');
```
The export transforms cents to decimal display (`/ 100`) in the `map()` method — the database stores integers, Excel shows formatted values.

---

## Phase 10 — Dashboard

### Goal
The dashboard displays live KPIs, charts, and alerts fetched from the API, all loading within 500ms.

### Backend — what to understand

**KPI queries must be fast — use indexed columns only:**
```php
public function getKpis(int $companyId): array
{
    $currentMonth = now()->format('Y-m');

    return [
        'revenue_mtd'     => Invoice::where('company_id', $companyId)
                                    ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$currentMonth])
                                    ->where('status', '!=', 'draft')
                                    ->sum('total'),
        'outstanding_ar'  => Invoice::where('company_id', $companyId)
                                    ->whereIn('status', ['unpaid', 'partial'])
                                    ->selectRaw('SUM(total - paid_amount)')->value(0),
        // ...
    ];
}
```
All returned values are integers (cents). The frontend formats them via `formatCents()`.

### Frontend — what to understand

**Recharts data transformation:**
```tsx
// API returns: [{ month: '2024-01', revenue: 450000 }, ...]
// Recharts needs the display value; format for the tooltip, not the data
<LineChart data={revenueTrend}>
  <YAxis tickFormatter={(value) => `$${(value / 100).toFixed(0)}`} />
  <Tooltip formatter={(value: number) => [`$${(value / 100).toFixed(2)}`, 'Revenue']} />
  <Line dataKey="revenue" />
</LineChart>
```
Keep raw cents in the chart data — only format in `tickFormatter` and `Tooltip formatter`. This way, Recharts' internal calculations (domain, ticks) use accurate integer math.

---

## Key patterns to internalize

### 1. Repository as a DB query namespace

```php
// WRONG — query in the Service
class InvoiceService
{
    public function getOverdue(): Collection
    {
        return Invoice::where('due_date', '<', now())
                      ->where('status', '!=', 'paid')
                      ->get();
    }
}

// RIGHT — query in the Repository, Service stays clean
class InvoiceRepository extends BaseRepository
{
    public function overdue(): Collection
    {
        return $this->model
            ->where('due_date', '<', now())
            ->where('status', '!=', 'paid')
            ->get();
    }
}
class InvoiceService
{
    public function getOverdue(): Collection
    {
        return $this->invoiceRepo->overdue();
    }
}
```
The Service describes *what* to do. The Repository describes *how* to query. When you need to add a `company_id` scope to the overdue query, you change one place (the Repository) and every caller benefits.

### 2. DB::transaction() wrapping multi-step writes

```php
// Any operation that writes to more than one table must be atomic
DB::transaction(function () {
    $invoice  = Invoice::create([...]);    // write 1
    foreach ($lines as $line) {
        InvoiceLine::create([...]);        // writes 2..N
    }
    // If ANY write fails, ALL roll back — no orphaned invoices without lines
});
```
Use `DB::transaction()` everywhere you see multiple `create()` or `update()` calls in the same method. The rule: if partial success would leave the database in an inconsistent state, it needs a transaction.

### 3. API Resource as a response contract

```php
// WRONG — return the model directly
return response()->json($invoice);
// This leaks internal fields, returns snake_case vs camelCase inconsistently

// RIGHT — always go through a Resource
return $this->success(new InvoiceResource($invoice));

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'invoiceNumber' => $this->invoice_number,  // snake→camel conversion
            'total'         => $this->total,            // remains integer (cents)
            'totalFormatted'=> MoneyHelper::format($this->total), // display version
            'status'        => $this->status,
            'customer'      => new CustomerResource($this->whenLoaded('customer')),
        ];
    }
}
```
`whenLoaded()` prevents N+1: if `customer` wasn't eager-loaded, it outputs nothing rather than firing an extra query. The Resource is also where you add `totalFormatted` — the formatted display value for the frontend without requiring the frontend to know the cent conversion.

### 4. TanStack Query key hierarchy

```tsx
// Hierarchical keys make targeted invalidation possible
queryKey: ['invoices']                     // all invoices
queryKey: ['invoices', { status: 'unpaid' }] // filtered list
queryKey: ['invoices', invoiceId]           // single invoice

// After recording a payment, invalidate the specific invoice AND the list
queryClient.invalidateQueries({ queryKey: ['invoices', invoiceId] });
queryClient.invalidateQueries({ queryKey: ['invoices'] });
// TanStack Query invalidates any key that starts with ['invoices']
// if you use: queryClient.invalidateQueries({ queryKey: ['invoices'], exact: false })
```

### 5. PermissionGate as the UI enforcement layer

```tsx
// Wrap any sensitive action in PermissionGate — it renders nothing if unauthorized
<PermissionGate permission="manage-invoices">
  <Button onClick={confirmInvoice}>Confirm Invoice</Button>
</PermissionGate>

// For entire page sections:
<PermissionGate permission="manage-payroll" fallback={<AccessDeniedMessage />}>
  <PayrollApprovalPanel />
</PermissionGate>
```
This is defense-in-depth for the UI — the backend middleware is the real gate, but hiding buttons from unauthorized users prevents UX confusion and reduces noise in server logs.

### 6. Money as integers — the full contract

```
Backend in:   FormRequest validates: 'total' => 'required|integer|min:0'
Backend store: $invoice->total = $request->total  (integer stays integer)
Backend out:   InvoiceResource returns: 'total' => $this->total  (integer)
                                        'total_formatted' => MoneyHelper::format($this->total)

Frontend in:  <MoneyInput onChange={(cents) => setValue('total', cents)} />
              // MoneyInput shows "99.99", converts to 9999 on change
Frontend display: <span>{formatCents(invoice.total)}</span>  // 9999 → "99.99"
Frontend send: { total: 9999 }  // always send cents to API
```
Never convert to float anywhere in this chain. The conversion to display format happens at the last possible moment — in the template or formatter.

---

## Database migration order — why it matters

Laravel runs migrations in filename order (timestamp prefix). Foreign key constraints will fail if the referenced table doesn't exist yet.

| Order | Migration | Reason |
|-------|-----------|--------|
| 1 | `create_companies_table` | Referenced by every other table via `company_id` |
| 2 | `create_users_table` | Referenced by `employees.user_id`, `leave_requests.approved_by`, `pos_sessions.opened_by` |
| 3 | `create_departments_table` | Referenced by `positions.department_id`, `employees.department_id` |
| 4 | `create_positions_table` | Referenced by `employees.position_id` — must come after departments |
| 5 | `create_employees_table` | Referenced by `departments.manager_id` (circular!), attendance, leave_requests, payroll_items |
| 6 | `add_manager_id_to_departments` | Separate migration to add `manager_id` FK *after* employees table exists (resolves circular dependency) |
| 7 | `create_leave_types_table` | Referenced by `leave_requests.leave_type_id` |
| 8 | `create_leave_requests_table` | Needs employees, leave_types, users |
| 9 | `create_payroll_runs_table` | Referenced by `payroll_items.payroll_run_id` |
| 10 | `create_payroll_items_table` | Needs payroll_runs, employees |
| 11 | `create_accounts_table` | Self-referential (`parent_id`) — add with `->nullable()`, no circular issue |
| 12 | `create_currencies_table` | Independent |
| 13 | `create_journal_entries_table` | Referenced by `journal_lines`, `payments_received.journal_entry_id` |
| 14 | `create_journal_lines_table` | Needs journal_entries, accounts |
| 15 | `create_warehouses_table` | Referenced by stock_movements, stock_layers |
| 16 | `create_products_table` | Referenced by all line item tables |
| 17 | `create_stock_movements_table` | Needs products, warehouses |
| 18 | `create_stock_layers_table` | Needs products, warehouses |
| 19 | `create_customers_table` | Referenced by quotations, sales_orders, invoices |
| 20 | `create_quotations_table` | Needs customers |
| 21 | `create_quotation_lines_table` | Needs quotations, products |
| 22 | `create_sales_orders_table` | Needs customers, quotations (nullable) |
| 23 | `create_sales_order_lines_table` | Needs sales_orders, products |
| 24 | `create_invoices_table` | Needs customers, sales_orders (nullable) |
| 25 | `create_invoice_lines_table` | Needs invoices, products |
| 26 | `create_payments_received_table` | Needs invoices, journal_entries |
| 27 | `create_vendors_table` | Referenced by purchase_orders, vendor_bills |
| 28 | `create_purchase_orders_table` | Needs vendors |
| 29 | `create_purchase_order_lines_table` | Needs purchase_orders, products |
| 30 | `create_goods_received_notes_table` | Needs purchase_orders |
| 31 | `create_grn_lines_table` | Needs goods_received_notes, products |
| 32 | `create_vendor_bills_table` | Needs vendors, goods_received_notes (nullable) |
| 33 | `create_payments_made_table` | Needs vendor_bills, journal_entries |
| 34 | `create_pos_sessions_table` | Needs users |
| 35 | `create_pos_transactions_table` | Needs pos_sessions |
| 36 | `create_pos_transaction_lines_table` | Needs pos_transactions, products |
| 37 | `create_pos_payments_table` | Needs pos_transactions |

**The circular dependency between departments and employees:**
`departments.manager_id → employees` and `employees.department_id → departments`. Solve it with two migrations: create `departments` without `manager_id`, create `employees` with `department_id`, then `ALTER TABLE departments ADD COLUMN manager_id` in a third migration. This is the only case where a separate `alter` migration is needed.

---

## How to use AI effectively to build this

### Bad prompt vs good prompt

**Bad:**
```
Generate the invoice controller for my ERP.
```
This produces a generic controller with Eloquent calls in the controller, no Resources, wrong namespace, wrong permissions.

**Good:**
```
Generate backend/app/Modules/Sales/Controllers/InvoiceController.php.
Namespace: App\Modules\Sales\Controllers.
Extends App\Base\BaseController.
Inject App\Modules\Sales\Services\SalesService via constructor.
Methods:
- index(Request $request): paginated list, returns InvoiceCollection, requires no extra permission (route already checks 'view-sales')
- show(int $id): returns InvoiceResource with customer and lines eager-loaded
- store(StoreInvoiceRequest $request): calls SalesService::createInvoice($request->validated()), returns InvoiceResource with 201 status
- confirm(int $id): calls SalesService::confirmInvoice($invoice), returns InvoiceResource
- pdf(int $id): dispatches GenerateInvoicePdfJob, returns JSON with message 'PDF generation queued'
All methods: use $this->success() or $this->created() from BaseController.
Never call Eloquent directly — only call $this->salesService methods.
```

### What AI does well in this stack
- Generating boilerplate (migrations, factories, seeders, CRUD controllers) from precise specifications
- Writing Blade views for DomPDF (it knows DomPDF's CSS limitations)
- TypeScript type definitions from a schema description
- TanStack Query hooks once you specify the queryKey and endpoint
- Writing test cases when you provide the class name, method name, and expected behavior

### What AI gets wrong frequently

1. **TanStack Query v4 vs v5 API:** AI uses the old `useQuery(['key'], fn)` two-argument form. Always specify "use TanStack Query v5 with the single object argument: `useQuery({ queryKey, queryFn })`".

2. **Spatie guard names:** AI defaults to `guard_name: 'web'`. All seeds and permission checks in this project use `guard_name: 'sanctum'`.

3. **Laravel 11 bootstrap pattern:** AI generates `app/Http/Kernel.php` middleware registration. In Laravel 11, middleware is registered in `bootstrap/app.php` using `->withMiddleware()`. Always specify "Laravel 11 — use bootstrap/app.php, not Kernel.php".

4. **Next.js App Router data fetching:** AI generates `getServerSideProps`. Remind it: "Next.js 14 App Router — no getServerSideProps. Use async Server Components for server-side data fetching, or useQuery in Client Components."

5. **DomPDF CSS:** AI generates `display: flex` and `grid` in Blade PDF views. DomPDF does not support modern CSS layout. Specify: "use HTML tables for layout, inline CSS only, no flexbox, no grid, no external stylesheets."

### Debugging template — paste this to AI with every error

```
## Error
[paste full error message or stack trace]

## Context
- File: [exact file path]
- Method/function: [method name]
- Laravel version: 11 / Next.js version: 14 / PHP: 8.3 / TanStack Query: v5
- What I was trying to do: [one sentence]

## Relevant code
[paste the method or component — not the whole file]

## What I already tried
[list 1-2 things you attempted]
```

---

## Security checklist — verify before shipping

- [ ] **company_id scoping on every query** — verify via Global Scope on all models; test by seeding two companies and confirming data doesn't bleed across
- [ ] **Sanctum middleware on all protected routes** — `auth:sanctum` must be on every route group except `/auth/login`
- [ ] **Permission middleware on all write routes** — e.g., confirming an invoice requires `permission:manage-invoices`, not just auth
- [ ] **FormRequest validation on every POST/PUT** — no raw `$request->all()` passed to create/update
- [ ] **Mass assignment protection** — every model has `$fillable` defined (or `$guarded = ['id']`); never use `Model::create($request->all())`
- [ ] **Soft deletes on financial models** — verify `SoftDeletes` trait is on Invoice, JournalEntry, PaymentReceived, VendorBill
- [ ] **Integer enforcement on money fields** — validate `'amount' => 'required|integer|min:0'` in all FormRequests that accept money
- [ ] **SQL injection prevention** — use Eloquent or parameterized `DB::select('...', [...])`. Never string-concatenate user input into queries
- [ ] **Rate limiting** — add `throttle:60,1` to auth routes; `throttle:120,1` to general API routes in `bootstrap/app.php`
- [ ] **CORS configuration** — `config/cors.php` should list only your frontend's domain, not `*`, in production
- [ ] **Token not in git** — `.env` is in `.gitignore`; `.env.example` has no real values
- [ ] **Storage permissions** — PDF payslips in `storage/app/payslips/` should not be publicly accessible; access via signed URLs only
- [ ] **XSS prevention on frontend** — never use `dangerouslySetInnerHTML` with user-supplied data; all user content rendered via React's default text nodes
- [ ] **Double-entry balance enforced in code** — `AccountingService::validateBalance()` is called in every path that creates journal entries (including the `confirmInvoice`, `confirmGrn`, and POS transaction flows)

---

## Environment variables — development vs production

```bash
# .env (development)
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erp_dev
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=redis          # Use 'sync' only if Redis isn't running locally
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

MAIL_MAILER=log                 # Emails logged to storage/logs — not sent
FILESYSTEM_DISK=local

SANCTUM_STATEFUL_DOMAINS=localhost:3000   # Not needed for token auth, but harmless
```

```bash
# .env (production)
APP_ENV=production
APP_DEBUG=false                  # CRITICAL — never true in prod (leaks stack traces)
APP_URL=https://api.yourdomain.com

DB_HOST=your-rds-endpoint.amazonaws.com
DB_DATABASE=erp_production
DB_USERNAME=erp_user
DB_PASSWORD=strong-random-password-from-secrets-manager

QUEUE_CONNECTION=redis
REDIS_HOST=your-redis-endpoint
REDIS_PASSWORD=redis-auth-token  # Required in production Redis

MAIL_MAILER=ses                  # Or smtp with real credentials
FILESYSTEM_DISK=s3               # Store PDFs in S3, not local disk
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_BUCKET=erp-documents-prod
```

```bash
# frontend/.env.local (development)
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

```bash
# frontend/.env.production (production)
NEXT_PUBLIC_API_URL=https://api.yourdomain.com/api
```

**Key differences dev → prod:**
- `APP_DEBUG=false` — non-negotiable; debug mode exposes file paths, SQL queries, env vars in error responses
- `MAIL_MAILER=log → ses/smtp` — dev logs emails, prod sends them
- `FILESYSTEM_DISK=local → s3` — dev stores PDFs on disk, prod stores in S3 (persistent across deployments)
- `QUEUE_CONNECTION=sync` is acceptable locally for debugging (jobs run immediately), but kills the purpose of queuing — use Redis locally once you start testing PDFs

---

## Common gotchas by area

### Backend (Laravel)

1. **Forgetting `company_id` in manual queries** — Global Scope handles Eloquent, but `DB::table(...)` queries bypass it. Always add `->where('company_id', auth()->user()->company_id)` to raw query builder calls.

2. **Returning Model directly instead of Resource** — `return response()->json($invoice)` bypasses your Resource class, leaking internal field names, missing formatted fields, and breaking the API contract. Always use `return $this->success(new InvoiceResource($invoice))`.

3. **Queue not running in development** — Jobs dispatch silently even if no worker is running. If you're not seeing PDFs generate, check `QUEUE_CONNECTION` and run `php artisan queue:work`.

4. **Spatie permission cache after seeding** — After running seeders that modify permissions, the permission cache may still return old data. Run `php artisan permission:cache-reset` before testing.

5. **Soft-deleted records in relationships** — Eloquent eager loading (`with('lines')`) respects soft deletes by default. If a journal line was soft-deleted but you still need it for a report, use `withTrashed()` explicitly.

6. **Integer overflow on large totals** — PHP integers are 64-bit, MySQL `INT` is 32-bit (max ~21 million dollars). Use `BIGINT` for `total`, `paid_amount`, and any column that aggregates multiple line items. Your migration should use `$table->unsignedBigInteger()`, not `$table->unsignedInteger()`.

### Frontend (Next.js)

1. **`useAuth` called outside `AuthProvider`** — If a component using `useAuth` is rendered before `AuthProvider` in the tree, you get "useAuth must be used inside AuthProvider". Check your `app/layout.tsx` provider order.

2. **TanStack Query v5 `onSuccess` removed** — `useMutation({ onSuccess: ... })` still works. But `useQuery({ onSuccess: ... })` was removed in v5. Handle success side effects with `useEffect(() => { if (data) { ... } }, [data])`.

3. **Stale `queryKey` after navigation** — If you navigate from `/invoices` to `/invoices/123` and back, the list is stale. Set `staleTime: 0` for lists that change frequently, or call `invalidateQueries` after mutations.

4. **Server Component trying to use hooks** — Adding `useAuth()` to a file without `'use client'` causes a build error. Default all pages in `(dashboard)/` to `'use client'` until you have a reason to make them Server Components.

5. **`formatCents` called with `undefined`** — API responses sometimes return `null` for optional money fields. Guard with `formatCents(invoice.paid_amount ?? 0)` everywhere.

6. **`params` in dynamic routes needs `await` in Next.js 15** — In Next.js 14 `params` is synchronous; in 15 it's a Promise. If you upgrade, `const { id } = await params` is required. AI often generates the async version prematurely.

### Third-party services

1. **DomPDF and UTF-8 Arabic text** — Arabic characters in PDF require loading an Arabic font explicitly. Add to the Blade view: `@php $pdf->getDomPDF()->getOptions()->setChroot(public_path()); @endphp` and reference a font via CSS `@font-face` using a local path.

2. **Maatwebsite Excel memory limit** — Exporting large datasets (>10,000 rows) can hit PHP's memory limit. Use the `WithChunkReading` concern with a chunk size of 1000. For very large exports, queue the job and store the file in S3.

3. **Redis connection refused in Docker** — If running Redis in Docker, `REDIS_HOST=127.0.0.1` won't work from the PHP container. Use the Docker service name (e.g., `REDIS_HOST=redis`).

### Testing

1. **Spatie permissions in tests** — Tests run with a fresh DB by default (`RefreshDatabase`). Permissions aren't seeded unless you explicitly call `$this->seed(RolePermissionSeeder::class)` in the test or use `DatabaseSeeder`.

2. **`actingAs` doesn't set company_id** — `$this->actingAs(User::factory()->create())` creates a user without a company, which breaks the Global Scope. Always factory-create a company and associate the user: `User::factory()->for(Company::factory())->create()`.

---

## Build order recommendation

Group by natural dependency chains. Never start a phase until its dependencies work end-to-end.

### Day 1 — Environment
1. Install Laravel 11 in `backend/`: `composer create-project laravel/laravel backend`
2. Configure `.env` (DB, Redis, APP_KEY)
3. Run `php artisan migrate` on a clean DB — confirm connection
4. Install Next.js 14 in `frontend/`: `npx create-next-app@latest frontend --typescript --tailwind --app`
5. Run `npm run dev` in `frontend/` — confirm it renders

### Day 2 — Laravel scaffold
6. Copy `app/Base/` classes (BaseController, BaseService, BaseRepository)
7. Copy `app/Helpers/MoneyHelper.php`
8. Configure `composer.json` PSR-4 to include `App\\Modules\\`
9. Write all migrations in correct order (see migration order section) — run `php artisan migrate`
10. Write `RolePermissionSeeder` — run it — confirm roles and permissions exist in DB

### Day 3 — Auth (backend)
11. Install Sanctum: `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
12. Install Spatie: `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
13. Write `AuthController` (login, logout, me) + `UserResource`
14. Write `LoginRequest` FormRequest
15. Test login with Postman/Thunder Client — get a token, call `/api/auth/me`

### Day 4 — Auth (frontend)
16. Install packages: `npm install @tanstack/react-query axios`
17. Install shadcn: `npx shadcn@latest init` + add Button, Input, Card, Toast components
18. Write `lib/api/client.ts` (Axios instance)
19. Write `providers/AuthProvider.tsx` + `providers/QueryProvider.tsx`
20. Write `lib/hooks/useAuth.ts` + `lib/hooks/usePermissions.ts`
21. Write `app/(auth)/login/page.tsx` — test login flow end-to-end

### Day 5 — Dashboard layout
22. Write `app/(dashboard)/layout.tsx` (sidebar + auth guard)
23. Write `components/layout/Sidebar.tsx` with permission-filtered nav items
24. Write `components/layout/PermissionGate.tsx`
25. Create placeholder `app/(dashboard)/page.tsx` (dashboard stub)
26. Test: login → redirect to dashboard → sidebar shows correct items per role

### Day 6-7 — HR module (backend)
27. Write HR models: Employee, Department, Position, Attendance, LeaveRequest, LeaveType, PayrollRun, PayrollItem
28. Write HR repositories (interfaces + implementations), bind in AppServiceProvider
29. Write `HrService` (CRUD) + `PayrollService` (calculateNetPay)
30. Write HR FormRequests + Resources
31. Write HR routes, test all endpoints with Postman

### Day 8 — HR module (frontend)
32. Write `lib/api/hr.ts` (all HR API calls)
33. Write employee list page + employee form (new/edit)
34. Write attendance page (clock in/out + manual entry grid)
35. Write leave management page (request form + approval table)

### Day 9-10 — Accounting foundation (backend)
36. Write Account, JournalEntry, JournalLine models
37. Write `AccountingService::createEntry()` with `validateBalance()`
38. Write `UnbalancedJournalException` + register in `bootstrap/app.php`
39. Write AccountController, JournalEntryController + Resources
40. Test: create a balanced journal entry, attempt an unbalanced one (expect 422)

### Day 11 — Accounting (frontend)
41. Write chart of accounts page (tree render)
42. Write journal entry form (dynamic lines + real-time balance indicator)

### Day 12-13 — Inventory module
43. Write Inventory models + StockService (addStock, deductStock with FIFO)
44. Test stock deduction with FIFO: create two layers at different costs, sell across both
45. Write product/warehouse/movement pages (frontend)

### Day 14-15 — Sales module
46. Write SalesService (confirmInvoice integrating StockService + AccountingService)
47. Test: create invoice → confirm → verify stock reduced + journal entry created
48. Write quotation builder, invoice view, payment modal (frontend)

### Day 16 — Purchasing module
49. Write PurchasingService (confirmGrn integrating StockService + AccountingService)
50. Test: receive GRN → verify stock increased + vendor bill created + journal entry

### Day 17-18 — POS module
51. Write PosService (session open/close, createTransaction)
52. Write POS terminal frontend (barcode scan, cart, payment split)
53. Test: complete a sale → verify stock deducted + journal entry + session cash updated

### Day 19-20 — Reports
54. Write trial balance, income statement, balance sheet SQL queries
55. Write AR/AP aging queries
56. Write Excel exports (TrialBalanceExport, etc.)
57. Write report pages with date range filters (frontend)

### Day 21 — Dashboard
58. Write KPI endpoint + revenue trend + top products/customers queries
59. Write dashboard page with Recharts (LineChart, BarChart)
60. Write low-stock widget + recent activity feed

### Day 22-23 — Polish
61. Add loading skeletons to all data tables
62. Add RTL support (`dir="rtl"`, Cairo font)
63. Add dark mode (Tailwind `dark:` + ThemeProvider)
64. Add toast notifications to all mutation success/error handlers
65. Add API rate limiting in `bootstrap/app.php`

### Day 24 — Hardening
66. Audit every route — confirm `permission:` middleware is on every write endpoint
67. Audit every Eloquent query — confirm `company_id` scoping is applied
68. Set `APP_DEBUG=false`, test error responses don't leak internals
69. Run `php artisan test` — fix failures
70. End-to-end smoke test: full sales cycle (quote → order → invoice → payment), full purchase cycle (PO → GRN → bill → payment), POS session (open → sale → close)
