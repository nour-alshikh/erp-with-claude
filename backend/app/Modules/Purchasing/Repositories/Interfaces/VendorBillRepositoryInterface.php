<?php

namespace App\Modules\Purchasing\Repositories\Interfaces;

use App\Modules\Purchasing\Models\VendorBill;
use Illuminate\Pagination\LengthAwarePaginator;

interface VendorBillRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function findOrFail(int $id): VendorBill;
    public function create(array $data): VendorBill;
    public function update(VendorBill $bill, array $data): VendorBill;
    public function nextBillNumber(): string;
}
