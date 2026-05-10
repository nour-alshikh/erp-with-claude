<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockOutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id'   => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'qty'          => ['required', 'integer', 'min:1'],
            'date'         => ['nullable', 'date'],
        ];
    }
}
