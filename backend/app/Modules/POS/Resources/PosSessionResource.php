<?php

namespace App\Modules\POS\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'status'         => $this->status,
            'opened_at'      => $this->opened_at?->toDateTimeString(),
            'closed_at'      => $this->closed_at?->toDateTimeString(),
            'opening_float'  => $this->opening_float,
            'expected_cash'  => $this->expected_cash,
            'actual_cash'    => $this->actual_cash,
            'variance'       => $this->actual_cash !== null
                ? $this->actual_cash - $this->expected_cash
                : null,
            'warehouse_id'   => $this->warehouse_id,
            'warehouse'      => $this->whenLoaded('warehouse', fn () => [
                'id'   => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ]),
            'opened_by'      => $this->whenLoaded('openedBy', fn () => [
                'id'   => $this->openedBy->id,
                'name' => $this->openedBy->name,
            ]),
        ];
    }
}
