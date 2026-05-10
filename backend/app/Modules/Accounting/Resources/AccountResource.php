<?php

namespace App\Modules\Accounting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'code'      => $this->code,
            'name'      => $this->name,
            'type'      => $this->type,
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,
            'parent'    => $this->whenLoaded('parent', fn () => [
                'id'   => $this->parent->id,
                'code' => $this->parent->code,
                'name' => $this->parent->name,
            ]),
            'children' => $this->whenLoaded(
                'children',
                fn () => AccountResource::collection($this->children)
            ),
        ];
    }
}
