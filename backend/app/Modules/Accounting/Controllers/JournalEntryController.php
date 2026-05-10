<?php

namespace App\Modules\Accounting\Controllers;

use App\Base\BaseController;
use App\Modules\Accounting\Requests\StoreJournalEntryRequest;
use App\Modules\Accounting\Resources\JournalEntryResource;
use App\Modules\Accounting\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalEntryController extends BaseController
{
    public function __construct(private readonly AccountingService $service) {}

    public function index(Request $request): JsonResponse
    {
        $entries = $this->service->list(
            $request->only(['date_from', 'date_to', 'type', 'status'])
        );
        return $this->success(JournalEntryResource::collection($entries));
    }

    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        $entry = $this->service->createEntry($request->validated(), $request->user()->company_id);
        return $this->created(new JournalEntryResource($entry));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new JournalEntryResource($this->service->get($id)));
    }

    public function update(StoreJournalEntryRequest $request, int $id): JsonResponse
    {
        $entry = $this->service->updateEntry($id, $request->validated(), $request->user()->company_id);
        return $this->success(new JournalEntryResource($entry));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteEntry($id);
        return $this->noContent();
    }

    public function post(int $id): JsonResponse
    {
        return $this->success(new JournalEntryResource($this->service->postEntry($id)));
    }
}
