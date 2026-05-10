<?php

namespace App\Modules\HR\Repositories;

use App\Base\BaseRepository;
use App\Modules\HR\Models\Attendance;
use App\Modules\HR\Repositories\Interfaces\AttendanceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    public function __construct(Attendance $model)
    {
        parent::__construct($model);
    }

    public function findOrFail(int $id): Attendance
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): Attendance
    {
        return $this->model->create($data);
    }

    public function update(Attendance $attendance, array $data): Attendance
    {
        $attendance->update($data);
        return $attendance->fresh();
    }

    public function forEmployee(int $employeeId, string $month): Collection
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->whereRaw('DATE_FORMAT(date, "%Y-%m") = ?', [$month])
            ->orderBy('date')
            ->get();
    }
}
