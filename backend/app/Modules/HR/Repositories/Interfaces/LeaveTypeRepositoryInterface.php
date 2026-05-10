<?php

namespace App\Modules\HR\Repositories\Interfaces;

use App\Modules\HR\Models\LeaveType;
use Illuminate\Database\Eloquent\Collection;

interface LeaveTypeRepositoryInterface
{
    public function all(): Collection;
    public function findOrFail(int $id): LeaveType;
    public function create(array $data): LeaveType;
    public function update(LeaveType $leaveType, array $data): LeaveType;
    public function delete(LeaveType $leaveType): bool;
}
