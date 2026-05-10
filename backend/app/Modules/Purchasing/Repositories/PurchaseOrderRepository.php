<?php

namespace App\Modules\Purchasing\Repositories;

use App\Base\BaseRepository;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Repositories\Interfaces\PurchaseOrderRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class PurchaseOrderRepository extends BaseRepository implements PurchaseOrderRepositoryInterface
{
    public function __construct(PurchaseOrder $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with('vendor')->orderByDesc('date')->paginate($perPage);
    }

    public function findOrFail(int $id): PurchaseOrder
    {
        return $this->model->with(['vendor', 'lines.product'])->findOrFail($id);
    }

    public function create(array $data): PurchaseOrder
    {
        return $this->model->create($data);
    }

    public function update(PurchaseOrder $po, array $data): PurchaseOrder
    {
        $po->update($data);
        return $po->fresh(['vendor', 'lines.product']);
    }

    public function delete(PurchaseOrder $po): bool
    {
        return $po->delete();
    }
}
