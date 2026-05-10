<?php

namespace App\Modules\Accounting\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends BaseModel
{
    protected $fillable = ['company_id', 'date', 'reference', 'description', 'type', 'status'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function getTotalDebitAttribute(): int
    {
        return $this->lines->sum('debit');
    }

    public function getTotalCreditAttribute(): int
    {
        return $this->lines->sum('credit');
    }
}
