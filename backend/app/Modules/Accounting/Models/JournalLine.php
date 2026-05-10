<?php

namespace App\Modules\Accounting\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends BaseModel
{
    protected $fillable = [
        'company_id', 'journal_entry_id', 'account_id', 'debit', 'credit', 'description',
    ];

    protected function casts(): array
    {
        return [
            'debit'  => 'integer',
            'credit' => 'integer',
        ];
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
