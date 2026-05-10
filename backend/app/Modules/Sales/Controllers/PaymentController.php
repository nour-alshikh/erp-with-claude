<?php

namespace App\Modules\Sales\Controllers;

use App\Base\BaseController;
use App\Modules\Sales\Requests\StorePaymentReceivedRequest;
use App\Modules\Sales\Resources\PaymentReceivedResource;
use App\Modules\Sales\Services\SalesService;
use Illuminate\Http\JsonResponse;

class PaymentController extends BaseController
{
    public function __construct(private readonly SalesService $service) {}

    public function store(StorePaymentReceivedRequest $request): JsonResponse
    {
        $payment = $this->service->recordPayment(
            $request->validated(),
            $request->user()->company_id,
        );
        return $this->created(new PaymentReceivedResource($payment));
    }
}
