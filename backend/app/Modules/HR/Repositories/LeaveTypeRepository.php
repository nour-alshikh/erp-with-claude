<?php

namespace App\Modules\HR\Repositories;

use App\Base\BaseRepository;
use App\Modules\HR\Models\LeaveType;
use App\Modules\HR\Repositories\Interfaces\LeaveTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LeaveTypeRepository extends BaseRepository implements LeaveTypeRepositoryInterface
{
    public function __construct(LeaveType $model)
    {
        parent::__construct($model);
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function findOrFail(int $id): LeaveType
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): LeaveType
    {
        return $this->model->create($data);
    }

    public function update(LeaveType $leaveType, array $data): LeaveType
    {
        $leaveType->update($data);
        return $leaveType->fresh();
    }

    public function delete(LeaveType $leaveType): bool
    {
        return $leaveType->delete();
    }
}
