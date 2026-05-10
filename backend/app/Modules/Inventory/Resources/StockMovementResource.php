<?php

namespace App\Modules\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'qty'          => $this->qty,
            'cost_per_unit' => $this->cost_per_unit,
            'date'         => $this->date?->toDateString(),
            'product_id'   => $this->product_id,
            'product'      => $this->whenLoaded('product', fn () => [
                'id'   => $this->product->id,
                'name' => $this->product->name,
                'sku'  => $this->product->sku,
            ]),
            'warehouse_id' => $this->warehouse_id,
            'warehouse'    => $this->whenLoaded('warehouse', fn () => [
                'id'   => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ]),
        ];
    }
}
