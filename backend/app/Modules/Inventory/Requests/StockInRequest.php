<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockInRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id'   => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'qty'          => ['required', 'integer', 'min:1'],
            'cost_per_unit' => ['required', 'integer', 'min:0'],
            'date'         => ['nullable', 'date'],
        ];
    }
}
