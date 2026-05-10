<?php

namespace App\Modules\Sales\Models;

use App\Models\BaseModel;
use App\Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends BaseModel
{
    protected $fillable = [
        'company_id', 'invoice_id', 'product_id', 'qty', 'unit_price', 'total',
    ];

    protected function casts(): array
    {
        return [
            'qty'        => 'integer',
            'unit_price' => 'integer',
            'total'      => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
