<?php

namespace App\Modules\Inventory\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends BaseModel
{
    protected $fillable = [
        'company_id', 'name', 'sku', 'barcode',
        'unit_of_measure', 'reorder_point', 'cost_price', 'selling_price',
    ];

    protected function casts(): array
    {
        return [
            'reorder_point' => 'integer',
            'cost_price'    => 'integer',
            'selling_price' => 'integer',
        ];
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockLayers(): HasMany
    {
        return $this->hasMany(StockLayer::class);
    }

    public function currentStock(int $warehouseId): int
    {
        return $this->stockMovements()
            ->where('warehouse_id', $warehouseId)
            ->selectRaw("SUM(CASE WHEN type = 'in' THEN qty ELSE -qty END) as stock")
            ->value('stock') ?? 0;
    }
}
