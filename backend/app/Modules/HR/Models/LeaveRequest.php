<?php

namespace App\Modules\HR\Models;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends BaseModel
{
    protected $fillable = [
        'company_id', 'employee_id', 'leave_type_id',
        'from_date', 'to_date', 'status', 'approved_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date'   => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
