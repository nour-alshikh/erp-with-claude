<?php

namespace App\Modules\Purchasing\Models;

use App\Models\BaseModel;
use App\Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends BaseModel
{
    protected $fillable = [
        'company_id', 'purchase_order_id', 'product_id', 'qty', 'unit_cost', 'total',
    ];

    protected function casts(): array
    {
        return [
            'qty'       => 'integer',
            'unit_cost' => 'integer',
            'total'     => 'integer',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
