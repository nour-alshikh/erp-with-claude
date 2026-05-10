<?php

namespace App\Modules\HR\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'employee_id' => $this->employee_id,
            'date'        => $this->date?->toDateString(),
            'clock_in'    => $this->clock_in,
            'clock_out'   => $this->clock_out,
            'type'        => $this->type,
        ];
    }
}
