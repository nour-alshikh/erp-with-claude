<?php

namespace App\Modules\HR\Repositories;

use App\Base\BaseRepository;
use App\Modules\HR\Models\Department;
use App\Modules\HR\Repositories\Interfaces\DepartmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class DepartmentRepository extends BaseRepository implements DepartmentRepositoryInterface
{
    public function __construct(Department $model)
    {
        parent::__construct($model);
    }

    public function all(): Collection
    {
        return $this->model->withCount('employees')->get();
    }

    public function findOrFail(int $id): Department
    {
        return $this->model->with(['manager', 'positions'])->findOrFail($id);
    }

    public function create(array $data): Department
    {
        return $this->model->create($data);
    }

    public function update(Department $department, array $data): Department
    {
        $department->update($data);
        return $department->fresh();
    }

    public function delete(Department $department): bool
    {
        return $department->delete();
    }
}
