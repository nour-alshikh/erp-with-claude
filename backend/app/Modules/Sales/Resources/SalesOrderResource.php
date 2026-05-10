<?php

namespace App\Modules\Sales\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'quotation_id' => $this->quotation_id,
            'customer'     => new CustomerResource($this->whenLoaded('customer')),
            'date'         => $this->date,
            'status'       => $this->status,
            'subtotal'     => $this->subtotal,
            'tax'          => $this->tax,
            'total'        => $this->total,
            'invoice_id'   => $this->whenLoaded('invoice', fn () => $this->invoice?->id),
            'lines'        => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($l) => [
                'id'         => $l->id,
                'product_id' => $l->product_id,
                'product'    => $l->product ? ['id' => $l->product->id, 'name' => $l->product->name] : null,
                'qty'        => $l->qty,
                'unit_price' => $l->unit_price,
                'total'      => $l->total,
            ])),
        ];
    }
}
