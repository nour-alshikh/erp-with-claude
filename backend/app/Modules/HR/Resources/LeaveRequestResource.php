<?php

namespace App\Modules\HR\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'employee_id' => $this->employee_id,
            'employee'    => $this->whenLoaded('employee', fn () => [
                'id'   => $this->employee->id,
                'name' => $this->employee->name,
            ]),
            'leave_type_id' => $this->leave_type_id,
            'leave_type'    => $this->whenLoaded('leaveType', fn () => [
                'id'   => $this->leaveType->id,
                'name' => $this->leaveType->name,
            ]),
            'from_date'   => $this->from_date?->toDateString(),
            'to_date'     => $this->to_date?->toDateString(),
            'status'      => $this->status,
            'approved_by' => $this->approved_by,
            'notes'       => $this->notes,
        ];
    }
}
