<?php

namespace App\Modules\POS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'actual_cash' => ['required', 'integer', 'min:0'],
        ];
    }
}
