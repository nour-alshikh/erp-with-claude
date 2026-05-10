<?php

namespace App\Modules\HR\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'month'  => $this->month,
            'year'   => $this->year,
            'status' => $this->status,
            'items'  => $this->whenLoaded(
                'payrollItems',
                fn () => PayrollItemResource::collection($this->payrollItems)
            ),
        ];
    }
}
