<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'     => ['sometimes', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:500'],
        ];
    }
}
