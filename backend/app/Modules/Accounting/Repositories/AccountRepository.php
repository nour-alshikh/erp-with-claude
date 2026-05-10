<?php

namespace App\Modules\Accounting\Repositories;

use App\Base\BaseRepository;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Repositories\Interfaces\AccountRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountRepository extends BaseRepository implements AccountRepositoryInterface
{
    public function __construct(Account $model)
    {
        parent::__construct($model);
    }

    public function all(): Collection
    {
        return $this->model->with('parent')->orderBy('code')->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with('parent')->orderBy('code')->paginate($perPage);
    }

    public function findOrFail(int $id): Account
    {
        return $this->model->with(['parent', 'children'])->findOrFail($id);
    }

    public function create(array $data): Account
    {
        return $this->model->create($data);
    }

    public function update(Account $account, array $data): Account
    {
        $account->update($data);
        return $account->fresh('parent');
    }

    public function delete(Account $account): bool
    {
        return $account->delete();
    }

    public function roots(): Collection
    {
        return $this->model->whereNull('parent_id')->with('children')->orderBy('code')->get();
    }
}
