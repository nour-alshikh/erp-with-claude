<?php

namespace App\Modules\Sales\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends BaseModel
{
    protected $fillable = [
        'company_id', 'sales_order_id', 'customer_id', 'invoice_number',
        'date', 'due_date', 'status', 'subtotal', 'tax', 'total', 'paid_amount',
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'due_date'    => 'date',
            'subtotal'    => 'integer',
            'tax'         => 'integer',
            'total'       => 'integer',
            'paid_amount' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentReceived::class);
    }

    public function getBalanceDueAttribute(): int
    {
        return $this->total - $this->paid_amount;
    }
}
