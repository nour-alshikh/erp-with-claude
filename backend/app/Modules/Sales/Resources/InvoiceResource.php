<?php

namespace App\Modules\Sales\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'invoice_number' => $this->invoice_number,
            'sales_order_id' => $this->sales_order_id,
            'customer'       => new CustomerResource($this->whenLoaded('customer')),
            'date'           => $this->date,
            'due_date'       => $this->due_date,
            'status'         => $this->status,
            'subtotal'       => $this->subtotal,
            'tax'            => $this->tax,
            'total'          => $this->total,
            'paid_amount'    => $this->paid_amount,
            'balance_due'    => $this->balance_due,
            'lines'          => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($l) => [
                'id'         => $l->id,
                'product_id' => $l->product_id,
                'product'    => $l->product ? ['id' => $l->product->id, 'name' => $l->product->name] : null,
                'qty'        => $l->qty,
                'unit_price' => $l->unit_price,
                'total'      => $l->total,
            ])),
            'payments'       => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($p) => [
                'id'     => $p->id,
                'amount' => $p->amount,
                'date'   => $p->date,
                'method' => $p->method,
                'notes'  => $p->notes,
            ])),
        ];
    }
}
