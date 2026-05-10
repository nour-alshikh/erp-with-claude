<?php

namespace App\Modules\Purchasing\Controllers;

use App\Base\BaseController;
use App\Modules\Purchasing\Requests\StoreVendorRequest;
use App\Modules\Purchasing\Requests\UpdateVendorRequest;
use App\Modules\Purchasing\Resources\VendorResource;
use App\Modules\Purchasing\Services\PurchasingService;
use Illuminate\Http\JsonResponse;

class VendorController extends BaseController
{
    public function __construct(private readonly PurchasingService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(VendorResource::collection($this->service->listVendors()));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new VendorResource($this->service->getVendor($id)));
    }

    public function store(StoreVendorRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), ['company_id' => $request->user()->company_id]);
        return $this->created(new VendorResource($this->service->createVendor($data)));
    }

    public function update(UpdateVendorRequest $request, int $id): JsonResponse
    {
        return $this->success(new VendorResource($this->service->updateVendor($id, $request->validated())));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteVendor($id);
        return $this->noContent();
    }
}
