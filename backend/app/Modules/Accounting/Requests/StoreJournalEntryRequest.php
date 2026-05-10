<?php

namespace App\Modules\Accounting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date'                   => ['required', 'date'],
            'reference'              => ['nullable', 'string', 'max:100'],
            'description'            => ['nullable', 'string', 'max:1000'],
            'lines'                  => ['required', 'array', 'min:2'],
            'lines.*.account_id'     => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.debit'          => ['required', 'integer', 'min:0'],
            'lines.*.credit'         => ['required', 'integer', 'min:0'],
            'lines.*.description'    => ['nullable', 'string', 'max:255'],
        ];
    }
}
