<?php

namespace App\Modules\Accounting\Controllers;

use App\Base\BaseController;
use App\Modules\Accounting\Repositories\Interfaces\AccountRepositoryInterface;
use App\Modules\Accounting\Requests\StoreAccountRequest;
use App\Modules\Accounting\Requests\UpdateAccountRequest;
use App\Modules\Accounting\Resources\AccountResource;
use Illuminate\Http\JsonResponse;

class AccountController extends BaseController
{
    public function __construct(private readonly AccountRepositoryInterface $accounts) {}

    public function index(): JsonResponse
    {
        return $this->success(AccountResource::collection($this->accounts->all()));
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        return $this->created(new AccountResource($this->accounts->create($request->validated())));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new AccountResource($this->accounts->findOrFail($id)));
    }

    public function update(UpdateAccountRequest $request, int $id): JsonResponse
    {
        $account = $this->accounts->update($this->accounts->findOrFail($id), $request->validated());
        return $this->success(new AccountResource($account));
    }
}
