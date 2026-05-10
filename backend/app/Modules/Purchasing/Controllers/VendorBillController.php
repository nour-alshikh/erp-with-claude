<?php

namespace App\Modules\Purchasing\Controllers;

use App\Base\BaseController;
use App\Modules\Purchasing\Requests\StorePaymentMadeRequest;
use App\Modules\Purchasing\Resources\PaymentMadeResource;
use App\Modules\Purchasing\Resources\VendorBillResource;
use App\Modules\Purchasing\Services\PurchasingService;
use Illuminate\Http\JsonResponse;

class VendorBillController extends BaseController
{
    public function __construct(private readonly PurchasingService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(VendorBillResource::collection($this->service->listBills()));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new VendorBillResource($this->service->getBill($id)));
    }

    public function pay(StorePaymentMadeRequest $request): JsonResponse
    {
        $payment = $this->service->payBill($request->validated(), $request->user()->company_id);
        return $this->created(new PaymentMadeResource($payment));
    }
}
