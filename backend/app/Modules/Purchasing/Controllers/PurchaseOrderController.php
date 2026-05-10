<?php

namespace App\Modules\Purchasing\Controllers;

use App\Base\BaseController;
use App\Modules\Purchasing\Requests\StorePurchaseOrderRequest;
use App\Modules\Purchasing\Requests\UpdatePurchaseOrderRequest;
use App\Modules\Purchasing\Resources\PurchaseOrderResource;
use App\Modules\Purchasing\Services\PurchasingService;
use Illuminate\Http\JsonResponse;

class PurchaseOrderController extends BaseController
{
    public function __construct(private readonly PurchasingService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(PurchaseOrderResource::collection($this->service->listOrders()));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new PurchaseOrderResource($this->service->getOrder($id)));
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $po = $this->service->createOrder($request->validated(), $request->user()->company_id);
        return $this->created(new PurchaseOrderResource($po));
    }

    public function update(UpdatePurchaseOrderRequest $request, int $id): JsonResponse
    {
        return $this->success(new PurchaseOrderResource($this->service->updateOrder($id, $request->validated())));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteOrder($id);
        return $this->noContent();
    }

    public function send(int $id): JsonResponse
    {
        return $this->success(new PurchaseOrderResource($this->service->sendOrder($id)));
    }
}
