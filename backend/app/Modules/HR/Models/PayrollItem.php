<?php

namespace App\Modules\HR\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends BaseModel
{
    protected $fillable = [
        'company_id', 'payroll_run_id', 'employee_id', 'type', 'description', 'amount',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
