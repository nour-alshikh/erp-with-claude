<?php

namespace App\Modules\HR\Repositories\Interfaces;

use App\Modules\HR\Models\PayrollRun;
use Illuminate\Pagination\LengthAwarePaginator;

interface PayrollRunRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function findOrFail(int $id): PayrollRun;
    public function create(array $data): PayrollRun;
    public function update(PayrollRun $run, array $data): PayrollRun;
    public function forMonthYear(int $month, int $year): ?PayrollRun;
}
