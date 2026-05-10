<?php

namespace App\Modules\HR\Repositories\Interfaces;

use App\Modules\HR\Models\Attendance;
use Illuminate\Database\Eloquent\Collection;

interface AttendanceRepositoryInterface
{
    public function findOrFail(int $id): Attendance;
    public function create(array $data): Attendance;
    public function update(Attendance $attendance, array $data): Attendance;
    public function forEmployee(int $employeeId, string $month): Collection;
}
