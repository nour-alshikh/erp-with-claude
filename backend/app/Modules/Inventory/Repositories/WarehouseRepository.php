<?php

namespace App\Modules\Inventory\Repositories;

use App\Base\BaseRepository;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Repositories\Interfaces\WarehouseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class WarehouseRepository extends BaseRepository implements WarehouseRepositoryInterface
{
    public function __construct(Warehouse $model)
    {
        parent::__construct($model);
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function findOrFail(int $id): Warehouse
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): Warehouse
    {
        return $this->model->create($data);
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $warehouse->update($data);
        return $warehouse->fresh();
    }

    public function delete(Warehouse $warehouse): bool
    {
        return $warehouse->delete();
    }
}
