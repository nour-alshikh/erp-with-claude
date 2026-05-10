<?php

namespace App\Modules\HR\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends BaseModel
{
    protected $fillable = ['company_id', 'month', 'year', 'status'];

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }
}
