<?php

namespace App\Modules\Accounting\Repositories\Interfaces;

use App\Modules\Accounting\Models\JournalEntry;
use Illuminate\Pagination\LengthAwarePaginator;

interface JournalEntryRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function filter(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function findOrFail(int $id): JournalEntry;
    public function create(array $data): JournalEntry;
    public function update(JournalEntry $entry, array $data): JournalEntry;
    public function delete(JournalEntry $entry): bool;
}
