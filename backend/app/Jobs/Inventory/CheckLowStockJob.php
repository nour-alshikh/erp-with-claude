<?php

namespace App\Jobs\Inventory;

use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckLowStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $productId,
        private readonly int $warehouseId,
        private readonly int $currentQty,
    ) {}

    public function handle(): void
    {
        $product   = Product::find($this->productId);
        $warehouse = Warehouse::find($this->warehouseId);

        if (! $product || ! $warehouse) {
            return;
        }

        Log::warning('Low stock alert', [
            'product'       => $product->name,
            'sku'           => $product->sku,
            'warehouse'     => $warehouse->name,
            'current_qty'   => $this->currentQty,
            'reorder_point' => $product->reorder_point,
        ]);

        // Future: dispatch push notification / email to warehouse manager here
    }
}
