<?php

namespace App\Modules\HR\Controllers;

use App\Base\BaseController;
use App\Modules\HR\Repositories\Interfaces\LeaveTypeRepositoryInterface;
use App\Modules\HR\Requests\StoreLeaveTypeRequest;
use App\Modules\HR\Resources\LeaveTypeResource;
use Illuminate\Http\JsonResponse;

class LeaveTypeController extends BaseController
{
    public function __construct(private readonly LeaveTypeRepositoryInterface $leaveTypes) {}

    public function index(): JsonResponse
    {
        return $this->success(LeaveTypeResource::collection($this->leaveTypes->all()));
    }

    public function store(StoreLeaveTypeRequest $request): JsonResponse
    {
        return $this->created(new LeaveTypeResource($this->leaveTypes->create($request->validated())));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new LeaveTypeResource($this->leaveTypes->findOrFail($id)));
    }

    public function update(StoreLeaveTypeRequest $request, int $id): JsonResponse
    {
        $leaveType = $this->leaveTypes->update($this->leaveTypes->findOrFail($id), $request->validated());
        return $this->success(new LeaveTypeResource($leaveType));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->leaveTypes->delete($this->leaveTypes->findOrFail($id));
        return $this->noContent();
    }
}
