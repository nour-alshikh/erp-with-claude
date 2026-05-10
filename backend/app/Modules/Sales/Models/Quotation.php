<?php

namespace App\Modules\Sales\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quotation extends BaseModel
{
    protected $fillable = [
        'company_id', 'customer_id', 'date', 'valid_until',
        'status', 'subtotal', 'tax', 'total', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'valid_until' => 'date',
            'subtotal'    => 'integer',
            'tax'         => 'integer',
            'total'       => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuotationLine::class);
    }

    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class);
    }
}
