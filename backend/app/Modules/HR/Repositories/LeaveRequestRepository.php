<?php

namespace App\Modules\HR\Repositories;

use App\Base\BaseRepository;
use App\Modules\HR\Models\LeaveRequest;
use App\Modules\HR\Repositories\Interfaces\LeaveRequestRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class LeaveRequestRepository extends BaseRepository implements LeaveRequestRepositoryInterface
{
    public function __construct(LeaveRequest $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['employee', 'leaveType'])->latest()->paginate($perPage);
    }

    public function findOrFail(int $id): LeaveRequest
    {
        return $this->model->with(['employee', 'leaveType', 'approvedBy'])->findOrFail($id);
    }

    public function create(array $data): LeaveRequest
    {
        return $this->model->create($data);
    }

    public function update(LeaveRequest $leaveRequest, array $data): LeaveRequest
    {
        $leaveRequest->update($data);
        return $leaveRequest->fresh(['employee', 'leaveType']);
    }
}
