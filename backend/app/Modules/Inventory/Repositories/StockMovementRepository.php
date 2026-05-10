<?php

namespace App\Modules\Inventory\Repositories;

use App\Base\BaseRepository;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Repositories\Interfaces\StockMovementRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class StockMovementRepository extends BaseRepository implements StockMovementRepositoryInterface
{
    public function __construct(StockMovement $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['product', 'warehouse'])->orderByDesc('date')->paginate($perPage);
    }

    public function create(array $data): StockMovement
    {
        return $this->model->create($data);
    }

    public function forProduct(int $productId, int $warehouseId): Collection
    {
        return $this->model
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->orderByDesc('date')
            ->get();
    }
}
