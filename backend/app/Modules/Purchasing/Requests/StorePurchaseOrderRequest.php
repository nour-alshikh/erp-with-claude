<?php

namespace App\Modules\Purchasing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'vendor_id'          => ['required', 'integer', 'exists:vendors,id'],
            'date'               => ['required', 'date'],
            'notes'              => ['nullable', 'string'],
            'lines'              => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.qty'        => ['required', 'integer', 'min:1'],
            'lines.*.unit_cost'  => ['required', 'integer', 'min:0'],
        ];
    }
}
