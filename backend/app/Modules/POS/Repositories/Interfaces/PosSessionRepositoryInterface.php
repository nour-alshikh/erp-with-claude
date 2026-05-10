<?php

namespace App\Modules\POS\Repositories\Interfaces;

use App\Modules\POS\Models\PosSession;
use Illuminate\Pagination\LengthAwarePaginator;

interface PosSessionRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function findOrFail(int $id): PosSession;
    public function create(array $data): PosSession;
    public function update(PosSession $session, array $data): PosSession;
    public function activeForUser(int $userId): ?PosSession;
}
