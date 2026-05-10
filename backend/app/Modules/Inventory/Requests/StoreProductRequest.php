<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255'],
            'sku'             => ['nullable', 'string', 'max:100'],
            'barcode'         => ['nullable', 'string', 'max:100'],
            'unit_of_measure' => ['sometimes', 'string', 'max:20'],
            'reorder_point'   => ['sometimes', 'integer', 'min:0'],
            'cost_price'      => ['required', 'integer', 'min:0'],
            'selling_price'   => ['required', 'integer', 'min:0'],
        ];
    }
}
