<?php

namespace App\Modules\Reports\Controllers;

use App\Base\BaseController;
use App\Modules\Reports\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    public function __construct(private readonly DashboardService $service) {}

    public function index(Request $request): JsonResponse
    {
        $id = $request->user()->company_id;
        return $this->success([
            'kpis'            => $this->service->kpis($id),
            'revenue_trend'   => $this->service->revenueTrend($id),
            'top_products'    => $this->service->topProducts($id),
            'top_customers'   => $this->service->topCustomers($id),
            'low_stock'       => $this->service->lowStock($id),
            'recent_activity' => $this->service->recentActivity($id),
        ]);
    }

    public function kpis(Request $request): JsonResponse
    {
        return $this->success($this->service->kpis($request->user()->company_id));
    }

    public function revenueTrend(Request $request): JsonResponse
    {
        return $this->success($this->service->revenueTrend($request->user()->company_id));
    }

    public function topProducts(Request $request): JsonResponse
    {
        return $this->success($this->service->topProducts($request->user()->company_id));
    }

    public function topCustomers(Request $request): JsonResponse
    {
        return $this->success($this->service->topCustomers($request->user()->company_id));
    }

    public function lowStock(Request $request): JsonResponse
    {
        return $this->success($this->service->lowStock($request->user()->company_id));
    }

    public function recentActivity(Request $request): JsonResponse
    {
        return $this->success($this->service->recentActivity($request->user()->company_id));
    }
}
