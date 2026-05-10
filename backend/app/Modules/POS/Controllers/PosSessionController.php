<?php

namespace App\Modules\POS\Controllers;

use App\Base\BaseController;
use App\Modules\POS\Requests\CloseSessionRequest;
use App\Modules\POS\Requests\OpenSessionRequest;
use App\Modules\POS\Resources\PosSessionResource;
use App\Modules\POS\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosSessionController extends BaseController
{
    public function __construct(private readonly PosService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(PosSessionResource::collection($this->service->listSessions()));
    }

    public function current(Request $request): JsonResponse
    {
        $session = $this->service->currentSession($request->user()->id);
        return $this->success($session ? new PosSessionResource($session) : null);
    }

    public function open(OpenSessionRequest $request): JsonResponse
    {
        $session = $this->service->openSession(
            $request->user()->id,
            $request->user()->company_id,
            $request->validated('opening_float'),
            $request->validated('warehouse_id'),
        );
        return $this->created(new PosSessionResource($session));
    }

    public function close(CloseSessionRequest $request, int $id): JsonResponse
    {
        $session = $this->service->closeSession(
            $id,
            $request->user()->id,
            $request->validated('actual_cash'),
        );
        return $this->success(new PosSessionResource($session));
    }
}
