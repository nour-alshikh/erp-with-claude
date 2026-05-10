<?php

namespace App\Modules\Purchasing\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'date'      => $this->date?->toDateString(),
            'status'    => $this->status,
            'total'     => $this->total,
            'notes'     => $this->notes,
            'vendor_id' => $this->vendor_id,
            'vendor'    => $this->whenLoaded('vendor', fn () => [
                'id'   => $this->vendor->id,
                'name' => $this->vendor->name,
            ]),
            'lines' => $this->whenLoaded('lines', fn () =>
                $this->lines->map(fn ($l) => [
                    'id'         => $l->id,
                    'product_id' => $l->product_id,
                    'product'    => $l->relationLoaded('product') ? ['id' => $l->product->id, 'name' => $l->product->name] : null,
                    'qty'        => $l->qty,
                    'unit_cost'  => $l->unit_cost,
                    'total'      => $l->total,
                ])
            ),
        ];
    }
}
