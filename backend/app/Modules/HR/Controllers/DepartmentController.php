<?php

namespace App\Modules\HR\Controllers;

use App\Base\BaseController;
use App\Modules\HR\Repositories\Interfaces\DepartmentRepositoryInterface;
use App\Modules\HR\Requests\StoreDepartmentRequest;
use App\Modules\HR\Requests\UpdateDepartmentRequest;
use App\Modules\HR\Resources\DepartmentResource;
use Illuminate\Http\JsonResponse;

class DepartmentController extends BaseController
{
    public function __construct(private readonly DepartmentRepositoryInterface $departments) {}

    public function index(): JsonResponse
    {
        return $this->success(DepartmentResource::collection($this->departments->all()));
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        return $this->created(new DepartmentResource($this->departments->create($request->validated())));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new DepartmentResource($this->departments->findOrFail($id)));
    }

    public function update(UpdateDepartmentRequest $request, int $id): JsonResponse
    {
        $dept = $this->departments->update($this->departments->findOrFail($id), $request->validated());
        return $this->success(new DepartmentResource($dept));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->departments->delete($this->departments->findOrFail($id));
        return $this->noContent();
    }
}
