<?php

namespace App\Modules\HR\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'string', 'max:255'],
            'national_id'   => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position_id'   => ['nullable', 'integer', 'exists:positions,id'],
            'hire_date'     => ['nullable', 'date'],
            'base_salary'   => ['sometimes', 'integer', 'min:0'],
            'status'        => ['sometimes', 'in:active,inactive,terminated'],
        ];
    }
}
