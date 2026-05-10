<?php

namespace App\Modules\Sales\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['nullable', 'email', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'credit_limit' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
