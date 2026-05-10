<?php

namespace App\Modules\Purchasing\Controllers;

use App\Base\BaseController;
use App\Modules\Purchasing\Requests\StoreGrnRequest;
use App\Modules\Purchasing\Resources\GrnResource;
use App\Modules\Purchasing\Services\PurchasingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GrnController extends BaseController
{
    public function __construct(private readonly PurchasingService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(GrnResource::collection($this->service->listGrns()));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new GrnResource($this->service->getGrn($id)));
    }

    public function store(StoreGrnRequest $request): JsonResponse
    {
        $grn = $this->service->createGrn($request->validated(), $request->user()->company_id);
        return $this->created(new GrnResource($grn));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteGrn($id);
        return $this->noContent();
    }

    public function confirm(int $id, Request $request): JsonResponse
    {
        $grn = $this->service->confirmGrn($id, $request->user()->company_id);
        return $this->success(new GrnResource($grn));
    }
}
