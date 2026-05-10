<?php

namespace App\Modules\Sales\Controllers;

use App\Base\BaseController;
use App\Modules\Sales\Resources\InvoiceResource;
use App\Modules\Sales\Resources\SalesOrderResource;
use App\Modules\Sales\Services\SalesService;
use Illuminate\Http\JsonResponse;

class SalesOrderController extends BaseController
{
    public function __construct(private readonly SalesService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(SalesOrderResource::collection($this->service->listOrders()));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new SalesOrderResource($this->service->getOrder($id)));
    }

    public function convertToInvoice(int $id): JsonResponse
    {
        $invoice = $this->service->convertOrderToInvoice($id, request()->user()->company_id);
        return $this->created(new InvoiceResource($invoice));
    }
}
