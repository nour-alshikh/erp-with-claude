<?php

namespace App\Modules\Sales\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends BaseModel
{
    protected $fillable = ['company_id', 'name', 'email', 'phone', 'credit_limit', 'balance'];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'integer',
            'balance'      => 'integer',
        ];
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
