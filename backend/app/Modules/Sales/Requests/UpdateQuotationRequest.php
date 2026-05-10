<?php

namespace App\Modules\Sales\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuotationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id'        => ['required', 'integer', 'exists:customers,id'],
            'date'               => ['required', 'date'],
            'valid_until'        => ['nullable', 'date', 'after_or_equal:date'],
            'notes'              => ['nullable', 'string'],
            'lines'              => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.qty'        => ['required', 'integer', 'min:1'],
            'lines.*.unit_price' => ['required', 'integer', 'min:0'],
            'lines.*.discount'   => ['nullable', 'integer', 'min:0'],
        ];
    }
}
