<?php

namespace App\Modules\Purchasing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGrnRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'purchase_order_id'      => ['required', 'integer', 'exists:purchase_orders,id'],
            'warehouse_id'           => ['required', 'integer', 'exists:warehouses,id'],
            'date'                   => ['required', 'date'],
            'notes'                  => ['nullable', 'string'],
            'lines'                  => ['required', 'array', 'min:1'],
            'lines.*.product_id'     => ['required', 'integer', 'exists:products,id'],
            'lines.*.qty_received'   => ['required', 'integer', 'min:1'],
            'lines.*.unit_cost'      => ['required', 'integer', 'min:0'],
        ];
    }
}
