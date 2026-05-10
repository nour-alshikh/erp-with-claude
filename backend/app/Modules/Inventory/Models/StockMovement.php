<?php

namespace App\Modules\Inventory\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends BaseModel
{
    protected $fillable = [
        'company_id', 'product_id', 'warehouse_id', 'type',
        'qty', 'cost_per_unit', 'reference_type', 'reference_id', 'date',
    ];

    protected function casts(): array
    {
        return [
            'qty'           => 'integer',
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

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
