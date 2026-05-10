<?php

namespace App\Modules\Inventory\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLayer extends BaseModel
{
    protected $fillable = [
        'company_id', 'product_id', 'warehouse_id', 'qty_remaining', 'cost_per_unit', 'date',
    ];

    protected function casts(): array
    {
        return [
            'qty_remaining' => 'integer',
            'cost_per_unit' => 'integer',
            'date'          => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
