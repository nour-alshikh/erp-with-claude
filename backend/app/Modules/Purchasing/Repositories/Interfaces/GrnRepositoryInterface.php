<?php

namespace App\Modules\Purchasing\Repositories\Interfaces;

use App\Modules\Purchasing\Models\GoodsReceivedNote;
use Illuminate\Pagination\LengthAwarePaginator;

interface GrnRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function findOrFail(int $id): GoodsReceivedNote;
    public function create(array $data): GoodsReceivedNote;
    public function update(GoodsReceivedNote $grn, array $data): GoodsReceivedNote;
    public function delete(GoodsReceivedNote $grn): bool;
}
