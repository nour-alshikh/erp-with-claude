<?php

namespace App\Modules\HR\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualAttendanceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date'        => ['required', 'date'],
            'clock_in'    => ['nullable', 'date_format:H:i'],
            'clock_out'   => ['nullable', 'date_format:H:i'],
            'type'        => ['required', 'in:present,absent,leave'],
        ];
    }
}
