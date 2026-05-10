<?php

namespace App\Modules\Accounting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code'      => ['sometimes', 'string', 'max:20'],
            'name'      => ['sometimes', 'string', 'max:255'],
            'type'      => ['sometimes', 'in:asset,liability,equity,income,expense'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
