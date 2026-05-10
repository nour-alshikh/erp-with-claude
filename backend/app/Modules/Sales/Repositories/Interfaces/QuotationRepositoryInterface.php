<?php

namespace App\Modules\Sales\Repositories\Interfaces;

use App\Modules\Sales\Models\Quotation;
use Illuminate\Pagination\LengthAwarePaginator;

interface QuotationRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function findOrFail(int $id): Quotation;
    public function create(array $data): Quotation;
    public function update(Quotation $quotation, array $data): Quotation;
}
