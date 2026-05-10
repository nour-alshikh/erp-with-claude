<?php

namespace App\Modules\Sales\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesOrder extends BaseModel
{
    protected $fillable = [
        'company_id', 'quotation_id', 'customer_id', 'date', 'status', 'subtotal', 'tax', 'total',
    ];

    protected function casts(): array
    {
        return [
            'date'     => 'date',
            'subtotal' => 'integer',
            'tax'      => 'integer',
            'total'    => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}
