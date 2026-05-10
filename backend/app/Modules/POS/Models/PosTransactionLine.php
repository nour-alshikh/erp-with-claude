<?php

namespace App\Modules\POS\Models;

use App\Models\BaseModel;
use App\Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosTransactionLine extends BaseModel
{
    protected $fillable = [
        'company_id', 'pos_transaction_id', 'product_id', 'qty', 'unit_price', 'total',
    ];

    protected function casts(): array
    {
        return [
            'qty'        => 'integer',
            'unit_price' => 'integer',
            'total'      => 'integer',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
