<?php

namespace App\Modules\Inventory\Controllers;

use App\Base\BaseController;
use App\Modules\Inventory\Requests\StoreWarehouseRequest;
use App\Modules\Inventory\Requests\UpdateWarehouseRequest;
use App\Modules\Inventory\Resources\WarehouseResource;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\JsonResponse;

class WarehouseController extends BaseController
{
    public function __construct(private readonly InventoryService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(WarehouseResource::collection($this->service->listWarehouses()));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new WarehouseResource($this->service->getWarehouse($id)));
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), ['company_id' => $request->user()->company_id]);
        return $this->created(new WarehouseResource($this->service->createWarehouse($data)));
    }

    public function update(UpdateWarehouseRequest $request, int $id): JsonResponse
    {
        return $this->success(new WarehouseResource($this->service->updateWarehouse($id, $request->validated())));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteWarehouse($id);
        return $this->noContent();
    }
}
