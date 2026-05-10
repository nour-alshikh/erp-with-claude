<?php

namespace App\Modules\Sales\Repositories\Interfaces;

use App\Modules\Sales\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;

interface CustomerRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function findOrFail(int $id): Customer;
    public function create(array $data): Customer;
    public function update(Customer $customer, array $data): Customer;
    public function delete(Customer $customer): void;
}
