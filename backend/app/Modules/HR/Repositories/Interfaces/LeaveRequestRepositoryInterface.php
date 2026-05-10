<?php

namespace App\Modules\HR\Repositories\Interfaces;

use App\Modules\HR\Models\LeaveRequest;
use Illuminate\Pagination\LengthAwarePaginator;

interface LeaveRequestRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function findOrFail(int $id): LeaveRequest;
    public function create(array $data): LeaveRequest;
    public function update(LeaveRequest $leaveRequest, array $data): LeaveRequest;
}
