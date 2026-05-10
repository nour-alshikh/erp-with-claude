<?php

namespace App\Modules\HR\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends BaseModel
{
    protected $fillable = ['company_id', 'employee_id', 'date', 'clock_in', 'clock_out', 'type'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
