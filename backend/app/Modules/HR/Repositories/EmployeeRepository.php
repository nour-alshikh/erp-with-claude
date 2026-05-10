<?php

namespace App\Modules\HR\Repositories;

use App\Base\BaseRepository;
use App\Modules\HR\Models\Employee;
use App\Modules\HR\Repositories\Interfaces\EmployeeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeRepository extends BaseRepository implements EmployeeRepositoryInterface
{
    public function __construct(Employee $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['department', 'position'])->paginate($perPage);
    }

    public function findOrFail(int $id): Employee
    {
        return $this->model->with(['department', 'position', 'user'])->findOrFail($id);
    }

    public function create(array $data): Employee
    {
        return $this->model->create($data);
    }

    public function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);
        return $employee->fresh(['department', 'position']);
    }

    public function delete(Employee $employee): bool
    {
        return $employee->delete();
    }

    public function byDepartment(int $departmentId): Collection
    {
        return $this->model->where('department_id', $departmentId)->get();
    }

    public function allActive(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }
}
