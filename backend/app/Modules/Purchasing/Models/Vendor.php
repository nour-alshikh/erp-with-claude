<?php

namespace App\Modules\Purchasing\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends BaseModel
{
    protected $fillable = ['company_id', 'name', 'email', 'phone', 'balance'];

    protected function casts(): array
    {
        return ['balance' => 'integer'];
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function vendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }
}
