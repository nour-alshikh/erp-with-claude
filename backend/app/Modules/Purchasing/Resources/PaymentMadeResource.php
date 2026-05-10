<?php

namespace App\Modules\Purchasing\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMadeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'amount'         => $this->amount,
            'date'           => $this->date?->toDateString(),
            'method'         => $this->method,
            'notes'          => $this->notes,
            'vendor_bill_id' => $this->vendor_bill_id,
            'vendor_bill'    => $this->whenLoaded('vendorBill', fn () => [
                'id'          => $this->vendorBill->id,
                'bill_number' => $this->vendorBill->bill_number,
                'vendor'      => $this->vendorBill->relationLoaded('vendor')
                    ? ['id' => $this->vendorBill->vendor->id, 'name' => $this->vendorBill->vendor->name]
                    : null,
            ]),
        ];
    }
}
