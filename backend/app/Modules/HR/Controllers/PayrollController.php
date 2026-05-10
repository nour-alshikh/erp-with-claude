<?php

namespace App\Modules\HR\Controllers;

use App\Base\BaseController;
use App\Modules\HR\Requests\RunPayrollRequest;
use App\Modules\HR\Resources\PayrollRunResource;
use App\Modules\HR\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends BaseController
{
    public function __construct(private readonly PayrollService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(PayrollRunResource::collection($this->service->list()));
    }

    public function run(RunPayrollRequest $request): JsonResponse
    {
        $run = $this->service->run(
            $request->integer('month'),
            $request->integer('year'),
            $request->user()->company_id,
        );
        return $this->created(new PayrollRunResource($run));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new PayrollRunResource($this->service->get($id)));
    }

    public function approve(int $id): JsonResponse
    {
        return $this->success(new PayrollRunResource($this->service->approve($id)));
    }

    public function payslip(Request $request, int $id, int $emp): JsonResponse
    {
        $this->service->dispatchPayslip($id, $emp);
        return $this->success(['message' => 'Payslip generation queued.']);
    }
}
