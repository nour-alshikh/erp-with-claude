<?php

namespace App\Modules\POS\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosTransaction extends BaseModel
{
    protected $fillable = [
        'company_id', 'pos_session_id', 'transaction_number', 'date',
        'subtotal', 'tax', 'total', 'status',
    ];

    protected function casts(): array
    {
        return [
            'date'     => 'datetime',
            'subtotal' => 'integer',
            'tax'      => 'integer',
            'total'    => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PosTransactionLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class);
    }
}
