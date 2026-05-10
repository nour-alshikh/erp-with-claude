<?php

namespace App\Modules\HR\Controllers;

use App\Base\BaseController;
use App\Modules\HR\Requests\StoreEmployeeRequest;
use App\Modules\HR\Requests\UpdateEmployeeRequest;
use App\Modules\HR\Resources\EmployeeResource;
use App\Modules\HR\Services\EmployeeService;
use Illuminate\Http\JsonResponse;

class EmployeeController extends BaseController
{
    public function __construct(private readonly EmployeeService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(EmployeeResource::collection($this->service->list()));
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        return $this->created(new EmployeeResource($this->service->create($request->validated())));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new EmployeeResource($this->service->get($id)));
    }

    public function update(UpdateEmployeeRequest $request, int $id): JsonResponse
    {
        return $this->success(new EmployeeResource($this->service->update($id, $request->validated())));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->noContent();
    }
}
