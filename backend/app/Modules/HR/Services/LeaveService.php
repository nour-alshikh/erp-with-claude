<?php

namespace App\Modules\HR\Services;

use App\Base\BaseService;
use App\Modules\HR\Models\LeaveRequest;
use App\Modules\HR\Repositories\Interfaces\LeaveRequestRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class LeaveService extends BaseService
{
    public function __construct(
        private readonly LeaveRequestRepositoryInterface $leaves,
    ) {}

    public function list(): LengthAwarePaginator
    {
        return $this->leaves->paginate();
    }

    public function store(array $data): LeaveRequest
    {
        return $this->leaves->create($data);
    }

    public function approve(int $id, int $approvedBy): LeaveRequest
    {
        $request = $this->leaves->findOrFail($id);

        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only pending requests can be approved.']);
        }

        return $this->leaves->update($request, [
            'status'      => 'approved',
            'approved_by' => $approvedBy,
        ]);
    }

    public function reject(int $id, int $approvedBy): LeaveRequest
    {
        $request = $this->leaves->findOrFail($id);

        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only pending requests can be rejected.']);
        }

        return $this->leaves->update($request, [
            'status'      => 'rejected',
            'approved_by' => $approvedBy,
        ]);
    }
}
