<?php

namespace App\Modules\Purchasing\Repositories;

use App\Base\BaseRepository;
use App\Modules\Purchasing\Models\VendorBill;
use App\Modules\Purchasing\Repositories\Interfaces\VendorBillRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class VendorBillRepository extends BaseRepository implements VendorBillRepositoryInterface
{
    public function __construct(VendorBill $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with('vendor')->orderByDesc('date')->paginate($perPage);
    }

    public function findOrFail(int $id): VendorBill
    {
        return $this->model->with(['vendor', 'payments'])->findOrFail($id);
    }

    public function create(array $data): VendorBill
    {
        return $this->model->create($data);
    }

    public function update(VendorBill $bill, array $data): VendorBill
    {
        $bill->update($data);
        return $bill->fresh('vendor');
    }

    public function nextBillNumber(): string
    {
        $year  = now()->year;
        $count = $this->model->withTrashed()->whereYear('created_at', $year)->count() + 1;
        return sprintf('BILL-%d-%04d', $year, $count);
    }
}
