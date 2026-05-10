<?php

namespace App\Modules\Sales\Repositories\Interfaces;

use App\Modules\Sales\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

interface InvoiceRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function findOrFail(int $id): Invoice;
    public function create(array $data): Invoice;
    public function update(Invoice $invoice, array $data): Invoice;
    public function nextInvoiceNumber(): string;
}
