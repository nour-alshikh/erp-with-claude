<?php

namespace App\Modules\Sales\Repositories\Interfaces;

use App\Modules\Sales\Models\SalesOrder;
use Illuminate\Pagination\LengthAwarePaginator;

interface SalesOrderRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function findOrFail(int $id): SalesOrder;
    public function create(array $data): SalesOrder;
    public function update(SalesOrder $order, array $data): SalesOrder;
}
