<?php

namespace App\Modules\HR\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends BaseModel
{
    protected $fillable = ['company_id', 'name', 'days_allowed_per_year'];

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
