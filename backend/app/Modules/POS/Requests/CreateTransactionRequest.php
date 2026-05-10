<?php

namespace App\Modules\POS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'pos_session_id'       => ['required', 'integer', 'exists:pos_sessions,id'],
            'lines'                => ['required', 'array', 'min:1'],
            'lines.*.product_id'   => ['required', 'integer', 'exists:products,id'],
            'lines.*.qty'          => ['required', 'integer', 'min:1'],
            'lines.*.unit_price'   => ['required', 'integer', 'min:0'],
            'payments'             => ['required', 'array', 'min:1'],
            'payments.*.method'    => ['required', 'in:cash,card'],
            'payments.*.amount'    => ['required', 'integer', 'min:1'],
        ];
    }
}
