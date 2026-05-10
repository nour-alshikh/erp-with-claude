<?php

namespace App\Modules\Reports\Controllers;

use App\Base\BaseController;
use App\Modules\Reports\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends BaseController
{
    public function __construct(private readonly ReportService $service) {}

    public function trialBalance(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to   = $request->query('to',   now()->toDateString());

        return $this->success(
            $this->service->trialBalance($request->user()->company_id, $from, $to)
        );
    }

    public function incomeStatement(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to   = $request->query('to',   now()->toDateString());

        return $this->success(
            $this->service->incomeStatement($request->user()->company_id, $from, $to)
        );
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $asOf = $request->query('as_of', now()->toDateString());

        return $this->success(
            $this->service->balanceSheet($request->user()->company_id, $asOf)
        );
    }

    public function arAging(Request $request): JsonResponse
    {
        return $this->success(
            $this->service->arAging($request->user()->company_id)
        );
    }

    public function apAging(Request $request): JsonResponse
    {
        return $this->success(
            $this->service->apAging($request->user()->company_id)
        );
    }
}
