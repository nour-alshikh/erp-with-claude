<?php

namespace App\Modules\POS\Repositories;

use App\Base\BaseRepository;
use App\Modules\POS\Models\PosSession;
use App\Modules\POS\Repositories\Interfaces\PosSessionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class PosSessionRepository extends BaseRepository implements PosSessionRepositoryInterface
{
    public function __construct(PosSession $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with('openedBy')->orderByDesc('opened_at')->paginate($perPage);
    }

    public function findOrFail(int $id): PosSession
    {
        return $this->model->with(['openedBy', 'transactions'])->findOrFail($id);
    }

    public function create(array $data): PosSession
    {
        return $this->model->create($data);
    }

    public function update(PosSession $session, array $data): PosSession
    {
        $session->update($data);
        return $session->fresh();
    }

    public function activeForUser(int $userId): ?PosSession
    {
        return $this->model
            ->where('opened_by', $userId)
            ->where('status', 'open')
            ->first();
    }
}
