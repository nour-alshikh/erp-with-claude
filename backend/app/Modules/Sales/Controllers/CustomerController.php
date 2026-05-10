<?php

namespace App\Modules\Sales\Controllers;

use App\Base\BaseController;
use App\Modules\Sales\Requests\StoreCustomerRequest;
use App\Modules\Sales\Requests\UpdateCustomerRequest;
use App\Modules\Sales\Resources\CustomerResource;
use App\Modules\Sales\Services\SalesService;
use Illuminate\Http\JsonResponse;

class CustomerController extends BaseController
{
    public function __construct(private readonly SalesService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(CustomerResource::collection($this->service->listCustomers()));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new CustomerResource($this->service->getCustomer($id)));
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->service->createCustomer(array_merge(
            $request->validated(),
            ['company_id' => $request->user()->company_id],
        ));
        return $this->created(new CustomerResource($customer));
    }

    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        return $this->success(new CustomerResource(
            $this->service->updateCustomer($id, $request->validated())
        ));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteCustomer($id);
        return $this->noContent();
    }
}
