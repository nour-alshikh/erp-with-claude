<?php

namespace App\Modules\Purchasing\Repositories\Interfaces;

use App\Modules\Purchasing\Models\PurchaseOrder;
use Illuminate\Pagination\LengthAwarePaginator;

interface PurchaseOrderRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function findOrFail(int $id): PurchaseOrder;
    public function create(array $data): PurchaseOrder;
    public function update(PurchaseOrder $po, array $data): PurchaseOrder;
    public function delete(PurchaseOrder $po): bool;
}
