<?php

namespace App\Modules\HR\Repositories;

use App\Base\BaseRepository;
use App\Modules\HR\Models\Position;
use App\Modules\HR\Repositories\Interfaces\PositionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PositionRepository extends BaseRepository implements PositionRepositoryInterface
{
    public function __construct(Position $model)
    {
        parent::__construct($model);
    }

    public function all(): Collection
    {
        return $this->model->with('department')->get();
    }

    public function findOrFail(int $id): Position
    {
        return $this->model->with('department')->findOrFail($id);
    }

    public function create(array $data): Position
    {
        return $this->model->create($data);
    }

    public function update(Position $position, array $data): Position
    {
        $position->update($data);
        return $position->fresh(['department']);
    }

    public function delete(Position $position): bool
    {
        return $position->delete();
    }

    public function byDepartment(int $departmentId): Collection
    {
        return $this->model->where('department_id', $departmentId)->get();
    }
}
