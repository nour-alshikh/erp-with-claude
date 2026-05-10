<?php

namespace App\Modules\Sales\Models;

use App\Models\BaseModel;
use App\Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderLine extends BaseModel
{
    protected $fillable = [
        'company_id', 'sales_order_id', 'product_id', 'qty', 'unit_price', 'total',
    ];

    protected function casts(): array
    {
        return [
            'qty'        => 'integer',
            'unit_price' => 'integer',
            'total'      => 'integer',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
