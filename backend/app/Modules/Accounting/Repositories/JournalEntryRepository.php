<?php

namespace App\Modules\Accounting\Repositories;

use App\Base\BaseRepository;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Repositories\Interfaces\JournalEntryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class JournalEntryRepository extends BaseRepository implements JournalEntryRepositoryInterface
{
    public function __construct(JournalEntry $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with('lines.account')->orderByDesc('date')->paginate($perPage);
    }

    public function findOrFail(int $id): JournalEntry
    {
        return $this->model->with('lines.account')->findOrFail($id);
    }

    public function create(array $data): JournalEntry
    {
        return $this->model->create($data);
    }

    public function update(JournalEntry $entry, array $data): JournalEntry
    {
        $entry->update($data);
        return $entry->fresh('lines.account');
    }

    public function delete(JournalEntry $entry): bool
    {
        return $entry->delete();
    }
}
