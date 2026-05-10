<?php

namespace App\Modules\Inventory\Repositories\Interfaces;

use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;

interface WarehouseRepositoryInterface
{
    public function all(): Collection;
    public function findOrFail(int $id): Warehouse;
    public function create(array $data): Warehouse;
    public function update(Warehouse $warehouse, array $data): Warehouse;
    public function delete(Warehouse $warehouse): bool;
}
