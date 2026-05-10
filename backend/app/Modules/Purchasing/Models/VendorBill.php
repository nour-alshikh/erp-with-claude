<?php

namespace App\Modules\Purchasing\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorBill extends BaseModel
{
    protected $fillable = [
        'company_id', 'vendor_id', 'grn_id', 'bill_number',
        'date', 'due_date', 'status', 'total', 'paid_amount',
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'due_date'    => 'date',
            'total'       => 'integer',
            'paid_amount' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function goodsReceivedNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentMade::class);
    }

    public function getBalanceDueAttribute(): int
    {
        return $this->total - $this->paid_amount;
    }
}
