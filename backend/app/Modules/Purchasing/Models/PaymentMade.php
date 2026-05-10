<?php

namespace App\Modules\Purchasing\Models;

use App\Models\BaseModel;
use App\Modules\Accounting\Models\JournalEntry;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMade extends BaseModel
{
    protected $fillable = [
        'company_id', 'vendor_bill_id', 'amount', 'date', 'method', 'journal_entry_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'date'   => 'date',
        ];
    }

    public function vendorBill(): BelongsTo
    {
        return $this->belongsTo(VendorBill::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
