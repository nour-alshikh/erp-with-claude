<?php

namespace App\Modules\Purchasing\Models;

use App\Models\BaseModel;
use App\Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnLine extends BaseModel
{
    protected $fillable = [
        'company_id', 'grn_id', 'product_id', 'qty_ordered', 'qty_received', 'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'qty_ordered'  => 'integer',
            'qty_received' => 'integer',
            'unit_cost'    => 'integer',
        ];
    }

    public function goodsReceivedNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
