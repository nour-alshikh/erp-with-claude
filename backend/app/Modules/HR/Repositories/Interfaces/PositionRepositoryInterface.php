<?php

namespace App\Modules\HR\Repositories\Interfaces;

use App\Modules\HR\Models\Position;
use Illuminate\Database\Eloquent\Collection;

interface PositionRepositoryInterface
{
    public function all(): Collection;
    public function findOrFail(int $id): Position;
    public function create(array $data): Position;
    public function update(Position $position, array $data): Position;
    public function delete(Position $position): bool;
    public function byDepartment(int $departmentId): Collection;
}
