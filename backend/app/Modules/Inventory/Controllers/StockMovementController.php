<?php

namespace App\Modules\Inventory\Controllers;

use App\Base\BaseController;
use App\Modules\Inventory\Requests\StockInRequest;
use App\Modules\Inventory\Requests\StockOutRequest;
use App\Modules\Inventory\Requests\StockTransferRequest;
use App\Modules\Inventory\Resources\StockMovementResource;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\JsonResponse;

class StockMovementController extends BaseController
{
    public function __construct(private readonly InventoryService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(StockMovementResource::collection($this->service->listMovements()));
    }

    public function stockIn(StockInRequest $request): JsonResponse
    {
        $movement = $this->service->stockIn($request->validated(), $request->user()->company_id);
        return $this->created(new StockMovementResource($movement));
    }

    public function stockOut(StockOutRequest $request): JsonResponse
    {
        $movement = $this->service->stockOut($request->validated(), $request->user()->company_id);
        return $this->created(new StockMovementResource($movement));
    }

    public function transfer(StockTransferRequest $request): JsonResponse
    {
        [$out, $in] = $this->service->transfer($request->validated(), $request->user()->company_id);
        return $this->created([
            'out' => new StockMovementResource($out),
            'in'  => new StockMovementResource($in),
        ]);
    }

    public function levels(): JsonResponse
    {
        return $this->success($this->service->levels());
    }

    public function lowStock(): JsonResponse
    {
        return $this->success($this->service->lowStock());
    }
}
