<?php

namespace App\Modules\HR\Services;

use App\Base\BaseService;
use App\Modules\HR\Models\Employee;
use App\Modules\HR\Repositories\Interfaces\EmployeeRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeService extends BaseService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
    ) {}

    public function list(): LengthAwarePaginator
    {
        return $this->employees->paginate();
    }

    public function get(int $id): Employee
    {
        return $this->employees->findOrFail($id);
    }

    public function create(array $data): Employee
    {
        return $this->employees->create($data);
    }

    public function update(int $id, array $data): Employee
    {
        $employee = $this->employees->findOrFail($id);
        return $this->employees->update($employee, $data);
    }

    public function delete(int $id): void
    {
        $employee = $this->employees->findOrFail($id);
        $this->employees->delete($employee);
    }
}
