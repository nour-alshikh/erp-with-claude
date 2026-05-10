<?php

namespace App\Modules\Inventory\Repositories\Interfaces;

use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface StockMovementRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): StockMovement;
    public function forProduct(int $productId, int $warehouseId): Collection;
}
