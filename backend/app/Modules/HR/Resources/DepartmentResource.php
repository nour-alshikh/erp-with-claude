<?php

namespace App\Modules\HR\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'manager_id'      => $this->manager_id,
            'manager'         => $this->whenLoaded('manager', fn () => [
                'id'   => $this->manager->id,
                'name' => $this->manager->name,
            ]),
            'employees_count' => $this->whenCounted('employees'),
            'positions'       => $this->whenLoaded('positions', fn () =>
                PositionResource::collection($this->positions)
            ),
        ];
    }
}
