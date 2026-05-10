<?php

namespace App\Modules\HR\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'national_id'   => $this->national_id,
            'hire_date'     => $this->hire_date?->toDateString(),
            'base_salary'   => $this->base_salary,
            'status'        => $this->status,
            'department_id' => $this->department_id,
            'department'    => $this->whenLoaded('department', fn () => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ]),
            'position_id' => $this->position_id,
            'position'    => $this->whenLoaded('position', fn () => [
                'id'   => $this->position->id,
                'name' => $this->position->name,
            ]),
        ];
    }
}
