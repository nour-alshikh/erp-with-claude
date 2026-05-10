<?php

namespace App\Modules\Purchasing\Repositories;

use App\Base\BaseRepository;
use App\Modules\Purchasing\Models\GoodsReceivedNote;
use App\Modules\Purchasing\Repositories\Interfaces\GrnRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class GrnRepository extends BaseRepository implements GrnRepositoryInterface
{
    public function __construct(GoodsReceivedNote $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['purchaseOrder.vendor', 'warehouse'])
            ->orderByDesc('date')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): GoodsReceivedNote
    {
        return $this->model
            ->with(['purchaseOrder.vendor', 'warehouse', 'lines.product', 'vendorBill'])
            ->findOrFail($id);
    }

    public function create(array $data): GoodsReceivedNote
    {
        return $this->model->create($data);
    }

    public function update(GoodsReceivedNote $grn, array $data): GoodsReceivedNote
    {
        $grn->update($data);
        return $grn->fresh(['purchaseOrder.vendor', 'warehouse', 'lines.product', 'vendorBill']);
    }

    public function delete(GoodsReceivedNote $grn): bool
    {
        return $grn->delete();
    }
}
