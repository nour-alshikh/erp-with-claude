<?php

namespace App\Modules\Purchasing\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GoodsReceivedNote extends BaseModel
{
    protected $table = 'goods_received_notes';

    protected $fillable = ['company_id', 'purchase_order_id', 'warehouse_id', 'date', 'status', 'notes'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GrnLine::class, 'grn_id');
    }

    public function vendorBill(): HasOne
    {
        return $this->hasOne(VendorBill::class, 'grn_id');
    }
}
