<?php

namespace App\Modules\Accounting\Models;

use App\Models\BaseModel;

class Currency extends BaseModel
{
    protected $fillable = ['company_id', 'code', 'name', 'exchange_rate', 'is_base'];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:6',
            'is_base'       => 'boolean',
        ];
    }
}
