<?php

namespace App\Modules\Accounting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code'      => ['required', 'string', 'max:20'],
            'name'      => ['required', 'string', 'max:255'],
            'type'      => ['required', 'in:asset,liability,equity,income,expense'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
