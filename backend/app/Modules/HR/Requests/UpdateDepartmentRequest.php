<?php

namespace App\Modules\HR\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'       => ['sometimes', 'string', 'max:255'],
            'manager_id' => ['nullable', 'integer', 'exists:employees,id'],
        ];
    }
}
