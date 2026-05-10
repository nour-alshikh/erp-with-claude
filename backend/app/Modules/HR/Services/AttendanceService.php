<?php

namespace App\Modules\HR\Services;

use App\Base\BaseService;
use App\Modules\HR\Models\Attendance;
use App\Modules\HR\Repositories\Interfaces\AttendanceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class AttendanceService extends BaseService
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendance,
    ) {}

    public function forEmployee(int $employeeId, string $month): Collection
    {
        return $this->attendance->forEmployee($employeeId, $month);
    }

    public function clockIn(int $employeeId): Attendance
    {
        $today    = now()->toDateString();
        $existing = $this->attendance->forEmployee($employeeId, now()->format('Y-m'))
            ->firstWhere('date', $today);

        if ($existing) {
            if ($existing->clock_in) {
                throw ValidationException::withMessages(['clock_in' => 'Already clocked in today.']);
            }
            return $this->attendance->update($existing, ['clock_in' => now()->toTimeString(), 'type' => 'present']);
        }

        return $this->attendance->create([
            'employee_id' => $employeeId,
            'date'        => $today,
            'clock_in'    => now()->toTimeString(),
            'type'        => 'present',
        ]);
    }

    public function clockOut(int $employeeId): Attendance
    {
        $today    = now()->toDateString();
        $existing = $this->attendance->forEmployee($employeeId, now()->format('Y-m'))
            ->firstWhere('date', $today);

        if (! $existing || ! $existing->clock_in) {
            throw ValidationException::withMessages(['clock_out' => 'No clock-in found for today.']);
        }

        if ($existing->clock_out) {
            throw ValidationException::withMessages(['clock_out' => 'Already clocked out today.']);
        }

        return $this->attendance->update($existing, ['clock_out' => now()->toTimeString()]);
    }

    public function manual(array $data): Attendance
    {
        return $this->attendance->create($data);
    }
}
