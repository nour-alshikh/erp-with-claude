<?php

namespace App\Modules\HR\Repositories\Interfaces;

use App\Modules\HR\Models\Department;
use Illuminate\Database\Eloquent\Collection;

interface DepartmentRepositoryInterface
{
    public function all(): Collection;
    public function findOrFail(int $id): Department;
    public function create(array $data): Department;
    public function update(Department $department, array $data): Department;
    public function delete(Department $department): bool;
}
