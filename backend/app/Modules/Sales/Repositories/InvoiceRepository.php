<?php

namespace App\Modules\Sales\Repositories;

use App\Base\BaseRepository;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Repositories\Interfaces\InvoiceRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class InvoiceRepository extends BaseRepository implements InvoiceRepositoryInterface
{
    public function __construct(Invoice $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer'])->orderByDesc('date')->paginate($perPage);
    }

    public function findOrFail(int $id): Invoice
    {
        return $this->model->with(['customer', 'lines.product', 'payments'])->findOrFail($id);
    }

    public function create(array $data): Invoice
    {
        return $this->model->create($data);
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);
        return $invoice->fresh('customer');
    }

    public function nextInvoiceNumber(): string
    {
        $year  = now()->year;
        $count = $this->model->withTrashed()->whereYear('created_at', $year)->count() + 1;
        return sprintf('INV-%d-%04d', $year, $count);
    }
}
