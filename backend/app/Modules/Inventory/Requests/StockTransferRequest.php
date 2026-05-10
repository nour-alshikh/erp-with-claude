<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockTransferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id'        => ['required', 'integer', 'exists:products,id'],
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'to_warehouse_id'   => ['required', 'integer', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'qty'               => ['required', 'integer', 'min:1'],
            'date'              => ['nullable', 'date'],
        ];
    }
}
