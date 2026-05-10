<?php

namespace App\Modules\HR\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'national_id'   => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position_id'   => ['nullable', 'integer', 'exists:positions,id'],
            'hire_date'     => ['nullable', 'date'],
            'base_salary'   => ['required', 'integer', 'min:0'],
            'status'        => ['sometimes', 'in:active,inactive,terminated'],
        ];
    }
}
