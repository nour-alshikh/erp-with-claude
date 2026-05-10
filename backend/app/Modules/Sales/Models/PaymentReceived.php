<?php

namespace App\Modules\Sales\Models;

use App\Models\BaseModel;
use App\Modules\Accounting\Models\JournalEntry;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReceived extends BaseModel
{
    protected $fillable = [
        'company_id', 'invoice_id', 'amount', 'date', 'method', 'journal_entry_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'date'   => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
