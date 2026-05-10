<?php

namespace App\Modules\POS\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'transaction_number' => $this->transaction_number,
            'date'               => $this->date?->toDateTimeString(),
            'subtotal'           => $this->subtotal,
            'tax'                => $this->tax,
            'total'              => $this->total,
            'status'             => $this->status,
            'pos_session_id'     => $this->pos_session_id,
            'lines'              => $this->whenLoaded('lines', fn () =>
                $this->lines->map(fn ($l) => [
                    'id'           => $l->id,
                    'product_id'   => $l->product_id,
                    'product'      => $l->relationLoaded('product')
                        ? ['id' => $l->product->id, 'name' => $l->product->name]
                        : null,
                    'qty'          => $l->qty,
                    'unit_price'   => $l->unit_price,
                    'cost_per_unit' => $l->cost_per_unit,
                    'total'        => $l->total,
                ])
            ),
            'payments' => $this->whenLoaded('payments', fn () =>
                $this->payments->map(fn ($p) => [
                    'id'     => $p->id,
                    'method' => $p->method,
                    'amount' => $p->amount,
                ])
            ),
        ];
    }
}
