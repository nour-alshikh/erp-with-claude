<?php

namespace App\Modules\Accounting\Services;

use App\Base\BaseService;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Repositories\Interfaces\JournalEntryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingService extends BaseService
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $entries,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->entries->filter($filters);
    }

    public function get(int $id): JournalEntry
    {
        return $this->entries->findOrFail($id);
    }

    public function createEntry(array $data, int $companyId): JournalEntry
    {
        $this->assertBalanced($data['lines']);

        return DB::transaction(function () use ($data, $companyId) {
            $entry = $this->entries->create([
                'company_id'  => $companyId,
                'date'        => $data['date'],
                'reference'   => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'type'        => 'manual',
                'status'      => 'draft',
            ]);

            foreach ($data['lines'] as $line) {
                $entry->lines()->create([
                    'company_id'  => $companyId,
                    'account_id'  => $line['account_id'],
                    'debit'       => $line['debit'] ?? 0,
                    'credit'      => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $entry->fresh('lines.account');
        });
    }

    public function updateEntry(int $id, array $data, int $companyId): JournalEntry
    {
        $entry = $this->entries->findOrFail($id);

        if ($entry->status === 'posted') {
            throw ValidationException::withMessages(['status' => 'Posted entries cannot be modified.']);
        }

        $this->assertBalanced($data['lines']);

        return DB::transaction(function () use ($entry, $data, $companyId) {
            $this->entries->update($entry, [
                'date'        => $data['date'],
                'reference'   => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            $entry->lines()->delete();

            foreach ($data['lines'] as $line) {
                $entry->lines()->create([
                    'company_id'  => $companyId,
                    'account_id'  => $line['account_id'],
                    'debit'       => $line['debit'] ?? 0,
                    'credit'      => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $entry->fresh('lines.account');
        });
    }

    public function deleteEntry(int $id): void
    {
        $entry = $this->entries->findOrFail($id);

        if ($entry->status === 'posted') {
            throw ValidationException::withMessages(['status' => 'Posted entries cannot be deleted.']);
        }

        $this->entries->delete($entry);
    }

    public function postEntry(int $id): JournalEntry
    {
        $entry = $this->entries->findOrFail($id);

        if ($entry->status === 'posted') {
            throw ValidationException::withMessages(['status' => 'Entry is already posted.']);
        }

        return $this->entries->update($entry, ['status' => 'posted']);
    }

    private function assertBalanced(array $lines): void
    {
        $totalDebit  = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));

        if ($totalDebit === 0 && $totalCredit === 0) {
            throw ValidationException::withMessages([
                'lines' => 'Journal entry must have at least one non-zero amount.',
            ]);
        }

        if ($totalDebit !== $totalCredit) {
            throw ValidationException::withMessages([
                'lines' => "Entry is unbalanced: debits ({$totalDebit}) ≠ credits ({$totalCredit}).",
            ]);
        }
    }
}
