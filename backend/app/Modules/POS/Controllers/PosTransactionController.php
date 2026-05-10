<?php

namespace App\Modules\POS\Controllers;

use App\Base\BaseController;
use App\Modules\POS\Requests\CreateTransactionRequest;
use App\Modules\POS\Resources\PosTransactionResource;
use App\Modules\POS\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosTransactionController extends BaseController
{
    public function __construct(private readonly PosService $service) {}

    public function index(Request $request): JsonResponse
    {
        $sessionId = $request->integer('session_id');
        return $this->success(
            PosTransactionResource::collection($this->service->listTransactions($sessionId))
        );
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new PosTransactionResource($this->service->getTransaction($id)));
    }

    public function store(CreateTransactionRequest $request): JsonResponse
    {
        $tx = $this->service->createTransaction(
            $request->validated(),
            $request->user()->company_id,
        );
        return $this->created(new PosTransactionResource($tx));
    }

    public function void(int $id, Request $request): JsonResponse
    {
        $tx = $this->service->voidTransaction($id, $request->user()->company_id);
        return $this->success(new PosTransactionResource($tx));
    }
}
