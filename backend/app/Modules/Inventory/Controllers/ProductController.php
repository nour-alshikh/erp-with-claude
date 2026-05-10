<?php

namespace App\Modules\Inventory\Controllers;

use App\Base\BaseController;
use App\Modules\Inventory\Requests\StoreProductRequest;
use App\Modules\Inventory\Requests\UpdateProductRequest;
use App\Modules\Inventory\Resources\ProductResource;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\JsonResponse;

class ProductController extends BaseController
{
    public function __construct(private readonly InventoryService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(ProductResource::collection($this->service->listProducts()));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new ProductResource($this->service->getProduct($id)));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), ['company_id' => $request->user()->company_id]);
        return $this->created(new ProductResource($this->service->createProduct($data)));
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        return $this->success(new ProductResource($this->service->updateProduct($id, $request->validated())));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteProduct($id);
        return $this->noContent();
    }
}
