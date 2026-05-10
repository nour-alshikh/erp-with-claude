<?php

namespace App\Modules\POS\Models;

use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends BaseModel
{
    protected $fillable = [
        'company_id', 'warehouse_id', 'opened_by', 'closed_by', 'opened_at', 'closed_at',
        'opening_float', 'expected_cash', 'actual_cash', 'status',
    ];

    protected function casts(): array
    {
        return [
            'opened_at'      => 'datetime',
            'closed_at'      => 'datetime',
            'opening_float'  => 'integer',
            'expected_cash'  => 'integer',
            'actual_cash'    => 'integer',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class);
    }
}
