<?php

namespace App\Modules\Sales\Models;

use App\Models\BaseModel;
use App\Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationLine extends BaseModel
{
    protected $fillable = [
        'company_id', 'quotation_id', 'product_id', 'qty', 'unit_price', 'discount', 'total',
    ];

    protected function casts(): array
    {
        return [
            'qty'        => 'integer',
            'unit_price' => 'integer',
            'discount'   => 'integer',
            'total'      => 'integer',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
