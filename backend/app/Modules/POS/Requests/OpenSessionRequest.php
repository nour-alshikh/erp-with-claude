<?php

namespace App\Modules\POS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'opening_float' => ['required', 'integer', 'min:0'],
            'warehouse_id'  => ['required', 'integer', 'exists:warehouses,id'],
        ];
    }
}
