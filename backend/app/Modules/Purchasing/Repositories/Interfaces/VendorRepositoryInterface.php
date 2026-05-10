<?php

namespace App\Modules\Purchasing\Repositories\Interfaces;

use App\Modules\Purchasing\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;

interface VendorRepositoryInterface
{
    public function all(): Collection;
    public function findOrFail(int $id): Vendor;
    public function create(array $data): Vendor;
    public function update(Vendor $vendor, array $data): Vendor;
    public function delete(Vendor $vendor): bool;
}
