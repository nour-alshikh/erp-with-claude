<?php

namespace App\Modules\Purchasing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentMadeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'vendor_bill_id' => ['required', 'integer', 'exists:vendor_bills,id'],
            'amount'         => ['required', 'integer', 'min:1'],
            'date'           => ['required', 'date'],
            'method'         => ['sometimes', 'in:cash,bank,card'],
            'notes'          => ['nullable', 'string'],
        ];
    }
}
