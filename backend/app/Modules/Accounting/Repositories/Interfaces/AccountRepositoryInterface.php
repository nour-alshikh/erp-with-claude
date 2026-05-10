<?php

namespace App\Modules\Accounting\Repositories\Interfaces;

use App\Modules\Accounting\Models\Account;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AccountRepositoryInterface
{
    public function all(): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function findOrFail(int $id): Account;
    public function create(array $data): Account;
    public function update(Account $account, array $data): Account;
    public function delete(Account $account): bool;
    public function roots(): Collection;
}
