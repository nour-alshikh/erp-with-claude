<?php

namespace App\Modules\Sales\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmInvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
        ];
    }
}
