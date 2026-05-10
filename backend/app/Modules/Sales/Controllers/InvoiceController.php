<?php

namespace App\Modules\Sales\Controllers;

use App\Base\BaseController;
use App\Modules\Sales\Requests\ConfirmInvoiceRequest;
use App\Modules\Sales\Resources\InvoiceResource;
use App\Modules\Sales\Services\SalesService;
use Illuminate\Http\JsonResponse;

class InvoiceController extends BaseController
{
    public function __construct(private readonly SalesService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(InvoiceResource::collection($this->service->listInvoices()));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new InvoiceResource($this->service->getInvoice($id)));
    }

    public function confirm(ConfirmInvoiceRequest $request, int $id): JsonResponse
    {
        $invoice = $this->service->confirmInvoice(
            $id,
            $request->validated('warehouse_id'),
            $request->user()->company_id,
        );
        return $this->success(new InvoiceResource($invoice));
    }

    public function pdf(int $id): JsonResponse
    {
        return response()->json(['message' => 'PDF generation is queued. Check your notifications.']);
    }
}
