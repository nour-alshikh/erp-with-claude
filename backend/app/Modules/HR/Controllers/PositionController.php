<?php

namespace App\Modules\HR\Controllers;

use App\Base\BaseController;
use App\Modules\HR\Repositories\Interfaces\PositionRepositoryInterface;
use App\Modules\HR\Requests\StorePositionRequest;
use App\Modules\HR\Requests\UpdatePositionRequest;
use App\Modules\HR\Resources\PositionResource;
use Illuminate\Http\JsonResponse;

class PositionController extends BaseController
{
    public function __construct(private readonly PositionRepositoryInterface $positions) {}

    public function index(): JsonResponse
    {
        return $this->success(PositionResource::collection($this->positions->all()));
    }

    public function store(StorePositionRequest $request): JsonResponse
    {
        return $this->created(new PositionResource($this->positions->create($request->validated())));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new PositionResource($this->positions->findOrFail($id)));
    }

    public function update(UpdatePositionRequest $request, int $id): JsonResponse
    {
        $position = $this->positions->update($this->positions->findOrFail($id), $request->validated());
        return $this->success(new PositionResource($position));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->positions->delete($this->positions->findOrFail($id));
        return $this->noContent();
    }
}
