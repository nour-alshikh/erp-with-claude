<?php

namespace App\Modules\HR\Services;

use App\Base\BaseService;
use App\Jobs\HR\GeneratePayslipJob;
use App\Modules\HR\Models\PayrollRun;
use App\Modules\HR\Repositories\Interfaces\EmployeeRepositoryInterface;
use App\Modules\HR\Repositories\Interfaces\PayrollRunRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PayrollService extends BaseService
{
    public function __construct(
        private readonly PayrollRunRepositoryInterface $runs,
        private readonly EmployeeRepositoryInterface $employees,
    ) {}

    public function list(): LengthAwarePaginator
    {
        return $this->runs->paginate();
    }

    public function get(int $id): PayrollRun
    {
        return $this->runs->findOrFail($id);
    }

    public function run(int $month, int $year, int $companyId): PayrollRun
    {
        if ($this->runs->forMonthYear($month, $year)) {
            throw ValidationException::withMessages(['month' => 'Payroll for this period already exists.']);
        }

        $run = $this->runs->create([
            'company_id' => $companyId,
            'month'      => $month,
            'year'       => $year,
            'status'     => 'draft',
        ]);

        foreach ($this->employees->allActive() as $employee) {
            $run->payrollItems()->create([
                'company_id'  => $companyId,
                'employee_id' => $employee->id,
                'type'        => 'earning',
                'description' => 'Base Salary',
                'amount'      => $employee->base_salary,
            ]);
        }

        return $run->fresh(['payrollItems.employee']);
    }

    public function approve(int $id): PayrollRun
    {
        $run = $this->runs->findOrFail($id);

        if ($run->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft payrolls can be approved.']);
        }

        return $this->runs->update($run, ['status' => 'approved']);
    }

    public function dispatchPayslip(int $runId, int $employeeId): void
    {
        GeneratePayslipJob::dispatch($runId, $employeeId);
    }
}
