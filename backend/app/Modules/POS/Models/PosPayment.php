<?php

namespace App\Modules\POS\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosPayment extends BaseModel
{
    protected $fillable = ['company_id', 'pos_transaction_id', 'method', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }
}
