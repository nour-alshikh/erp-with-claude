<?php

namespace App\Modules\Sales\Controllers;

use App\Base\BaseController;
use App\Modules\Sales\Requests\StoreQuotationRequest;
use App\Modules\Sales\Requests\UpdateQuotationRequest;
use App\Modules\Sales\Resources\QuotationResource;
use App\Modules\Sales\Resources\SalesOrderResource;
use App\Modules\Sales\Services\SalesService;
use Illuminate\Http\JsonResponse;

class QuotationController extends BaseController
{
    public function __construct(private readonly SalesService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(QuotationResource::collection($this->service->listQuotations()));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new QuotationResource($this->service->getQuotation($id)));
    }

    public function store(StoreQuotationRequest $request): JsonResponse
    {
        $quotation = $this->service->createQuotation(
            $request->validated(),
            $request->user()->company_id,
        );
        return $this->created(new QuotationResource($quotation));
    }

    public function update(UpdateQuotationRequest $request, int $id): JsonResponse
    {
        return $this->success(new QuotationResource(
            $this->service->updateQuotation($id, $request->validated(), $request->user()->company_id)
        ));
    }

    public function convertToOrder(int $id): JsonResponse
    {
        $order = $this->service->convertQuotationToOrder($id, request()->user()->company_id);
        return $this->created(new SalesOrderResource($order));
    }
}
