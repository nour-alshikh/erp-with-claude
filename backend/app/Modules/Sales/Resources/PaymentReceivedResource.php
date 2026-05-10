<?php

namespace App\Modules\Sales\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentReceivedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'invoice_id' => $this->invoice_id,
            'invoice'    => $this->whenLoaded('invoice', fn () => [
                'id'             => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'customer'       => $this->invoice->relationLoaded('customer')
                    ? new CustomerResource($this->invoice->customer)
                    : null,
            ]),
            'amount'     => $this->amount,
            'date'       => $this->date,
            'method'     => $this->method,
            'notes'      => $this->notes,
        ];
    }
}
