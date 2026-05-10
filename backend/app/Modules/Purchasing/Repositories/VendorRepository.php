<?php

namespace App\Modules\Purchasing\Repositories;

use App\Base\BaseRepository;
use App\Modules\Purchasing\Models\Vendor;
use App\Modules\Purchasing\Repositories\Interfaces\VendorRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class VendorRepository extends BaseRepository implements VendorRepositoryInterface
{
    public function __construct(Vendor $model)
    {
        parent::__construct($model);
    }

    public function all(): Collection
    {
        return $this->model->orderBy('name')->get();
    }

    public function findOrFail(int $id): Vendor
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): Vendor
    {
        return $this->model->create($data);
    }

    public function update(Vendor $vendor, array $data): Vendor
    {
        $vendor->update($data);
        return $vendor->fresh();
    }

    public function delete(Vendor $vendor): bool
    {
        return $vendor->delete();
    }
}
