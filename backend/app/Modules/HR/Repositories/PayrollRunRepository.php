<?php

namespace App\Modules\HR\Repositories;

use App\Base\BaseRepository;
use App\Modules\HR\Models\PayrollRun;
use App\Modules\HR\Repositories\Interfaces\PayrollRunRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class PayrollRunRepository extends BaseRepository implements PayrollRunRepositoryInterface
{
    public function __construct(PayrollRun $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->orderByDesc('year')->orderByDesc('month')->paginate($perPage);
    }

    public function findOrFail(int $id): PayrollRun
    {
        return $this->model->with(['payrollItems.employee'])->findOrFail($id);
    }

    public function create(array $data): PayrollRun
    {
        return $this->model->create($data);
    }

    public function update(PayrollRun $run, array $data): PayrollRun
    {
        $run->update($data);
        return $run->fresh();
    }

    public function forMonthYear(int $month, int $year): ?PayrollRun
    {
        return $this->model->where('month', $month)->where('year', $year)->first();
    }
}
