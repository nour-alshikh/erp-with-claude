<?php

namespace App\Modules\Purchasing\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'bill_number' => $this->bill_number,
            'date'        => $this->date?->toDateString(),
            'due_date'    => $this->due_date?->toDateString(),
            'status'      => $this->status,
            'total'       => $this->total,
            'paid_amount' => $this->paid_amount,
            'balance_due' => $this->balance_due,
            'vendor_id'   => $this->vendor_id,
            'vendor'      => $this->whenLoaded('vendor', fn () => [
                'id'   => $this->vendor->id,
                'name' => $this->vendor->name,
            ]),
            'payments' => $this->whenLoaded('payments', fn () =>
                $this->payments->map(fn ($p) => [
                    'id'     => $p->id,
                    'amount' => $p->amount,
                    'date'   => $p->date?->toDateString(),
                    'method' => $p->method,
                ])
            ),
        ];
    }
}
