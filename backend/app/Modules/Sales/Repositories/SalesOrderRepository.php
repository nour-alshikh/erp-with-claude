<?php

namespace App\Modules\Sales\Repositories;

use App\Base\BaseRepository;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Sales\Repositories\Interfaces\SalesOrderRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class SalesOrderRepository extends BaseRepository implements SalesOrderRepositoryInterface
{
    public function __construct(SalesOrder $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer'])->orderByDesc('date')->paginate($perPage);
    }

    public function findOrFail(int $id): SalesOrder
    {
        return $this->model->with(['customer', 'lines.product', 'invoice'])->findOrFail($id);
    }

    public function create(array $data): SalesOrder
    {
        return $this->model->create($data);
    }

    public function update(SalesOrder $order, array $data): SalesOrder
    {
        $order->update($data);
        return $order->fresh(['customer', 'lines.product']);
    }
}
