<?php

namespace App\Modules\Accounting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'date'         => $this->date?->toDateString(),
            'reference'    => $this->reference,
            'description'  => $this->description,
            'type'         => $this->type,
            'status'       => $this->status,
            'total_debit'  => $this->totalDebit,
            'total_credit' => $this->totalCredit,
            'lines'        => $this->whenLoaded(
                'lines',
                fn () => JournalLineResource::collection($this->lines)
            ),
        ];
    }
}
