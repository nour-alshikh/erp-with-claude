<?php

namespace App\Modules\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'sku'             => $this->sku,
            'barcode'         => $this->barcode,
            'unit_of_measure' => $this->unit_of_measure,
            'reorder_point'   => $this->reorder_point,
            'cost_price'      => $this->cost_price,
            'selling_price'   => $this->selling_price,
        ];
    }
}
