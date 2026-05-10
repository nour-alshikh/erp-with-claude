<?php

namespace App\Modules\Sales\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentReceivedRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'amount'     => ['required', 'integer', 'min:1'],
            'date'       => ['nullable', 'date'],
            'method'     => ['nullable', 'string', 'in:cash,card,bank_transfer'],
            'notes'      => ['nullable', 'string'],
        ];
    }
}
