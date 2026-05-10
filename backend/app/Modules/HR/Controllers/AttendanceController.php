<?php

namespace App\Modules\HR\Controllers;

use App\Base\BaseController;
use App\Modules\HR\Requests\ClockInRequest;
use App\Modules\HR\Requests\ManualAttendanceRequest;
use App\Modules\HR\Resources\AttendanceResource;
use App\Modules\HR\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends BaseController
{
    public function __construct(private readonly AttendanceService $service) {}

    public function index(Request $request): JsonResponse
    {
        $employeeId = $request->integer('employee_id');
        $month      = $request->input('month', now()->format('Y-m'));
        return $this->success(AttendanceResource::collection($this->service->forEmployee($employeeId, $month)));
    }

    public function clockIn(ClockInRequest $request): JsonResponse
    {
        return $this->created(new AttendanceResource($this->service->clockIn($request->integer('employee_id'))));
    }

    public function clockOut(ClockInRequest $request): JsonResponse
    {
        return $this->success(new AttendanceResource($this->service->clockOut($request->integer('employee_id'))));
    }

    public function manual(ManualAttendanceRequest $request): JsonResponse
    {
        return $this->created(new AttendanceResource($this->service->manual($request->validated())));
    }
}
