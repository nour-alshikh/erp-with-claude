<?php

namespace App\Modules\HR\Controllers;

use App\Base\BaseController;
use App\Modules\HR\Requests\StoreLeaveRequestRequest;
use App\Modules\HR\Resources\LeaveRequestResource;
use App\Modules\HR\Services\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends BaseController
{
    public function __construct(private readonly LeaveService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(LeaveRequestResource::collection($this->service->list()));
    }

    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        return $this->created(new LeaveRequestResource($this->service->store($request->validated())));
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        return $this->success(new LeaveRequestResource($this->service->approve($id, $request->user()->id)));
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        return $this->success(new LeaveRequestResource($this->service->reject($id, $request->user()->id)));
    }
}
