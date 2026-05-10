<?php

namespace App\Modules\Purchasing\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'date'              => $this->date?->toDateString(),
            'status'            => $this->status,
            'notes'             => $this->notes,
            'purchase_order_id' => $this->purchase_order_id,
            'purchase_order'    => $this->whenLoaded('purchaseOrder', fn () => [
                'id'     => $this->purchaseOrder->id,
                'date'   => $this->purchaseOrder->date?->toDateString(),
                'vendor' => $this->purchaseOrder->relationLoaded('vendor')
                    ? ['id' => $this->purchaseOrder->vendor->id, 'name' => $this->purchaseOrder->vendor->name]
                    : null,
            ]),
            'warehouse_id' => $this->warehouse_id,
            'warehouse'    => $this->whenLoaded('warehouse', fn () => [
                'id'   => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ]),
            'lines' => $this->whenLoaded('lines', fn () =>
                $this->lines->map(fn ($l) => [
                    'id'           => $l->id,
                    'product_id'   => $l->product_id,
                    'product'      => $l->relationLoaded('product') ? ['id' => $l->product->id, 'name' => $l->product->name] : null,
                    'qty_ordered'  => $l->qty_ordered,
                    'qty_received' => $l->qty_received,
                    'unit_cost'    => $l->unit_cost,
                ])
            ),
            'vendor_bill' => $this->whenLoaded('vendorBill', fn () =>
                $this->vendorBill ? ['id' => $this->vendorBill->id, 'bill_number' => $this->vendorBill->bill_number] : null
            ),
        ];
    }
}
