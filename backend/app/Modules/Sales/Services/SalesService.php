<?php

namespace App\Modules\Sales\Services;

use App\Base\BaseService;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Inventory\Models\StockLayer;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\InvoiceLine;
use App\Modules\Sales\Models\PaymentReceived;
use App\Modules\Sales\Models\Quotation;
use App\Modules\Sales\Models\QuotationLine;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Sales\Models\SalesOrderLine;
use App\Modules\Sales\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Modules\Sales\Repositories\Interfaces\InvoiceRepositoryInterface;
use App\Modules\Sales\Repositories\Interfaces\QuotationRepositoryInterface;
use App\Modules\Sales\Repositories\Interfaces\SalesOrderRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesService extends BaseService
{
    public function __construct(
        private readonly CustomerRepositoryInterface    $customers,
        private readonly QuotationRepositoryInterface   $quotations,
        private readonly SalesOrderRepositoryInterface  $orders,
        private readonly InvoiceRepositoryInterface     $invoices,
    ) {}

    // ── Customers ─────────────────────────────────────────────────────────────

    public function listCustomers(): LengthAwarePaginator
    {
        return $this->customers->paginate();
    }

    public function getCustomer(int $id): Customer
    {
        return $this->customers->findOrFail($id);
    }

    public function createCustomer(array $data): Customer
    {
        return $this->customers->create($data);
    }

    public function updateCustomer(int $id, array $data): Customer
    {
        return $this->customers->update($this->customers->findOrFail($id), $data);
    }

    public function deleteCustomer(int $id): void
    {
        $this->customers->delete($this->customers->findOrFail($id));
    }

    // ── Quotations ────────────────────────────────────────────────────────────

    public function listQuotations(): LengthAwarePaginator
    {
        return $this->quotations->paginate();
    }

    public function getQuotation(int $id): Quotation
    {
        return $this->quotations->findOrFail($id);
    }

    public function createQuotation(array $data, int $companyId): Quotation
    {
        return DB::transaction(function () use ($data, $companyId) {
            [$subtotal, $tax, $total] = $this->sumLines($data['lines']);

            $quotation = $this->quotations->create([
                'company_id'  => $companyId,
                'customer_id' => $data['customer_id'],
                'date'        => $data['date'],
                'valid_until' => $data['valid_until'] ?? null,
                'status'      => 'draft',
                'subtotal'    => $subtotal,
                'tax'         => $tax,
                'total'       => $total,
                'notes'       => $data['notes'] ?? null,
            ]);

            $this->createQuotationLines($quotation, $data['lines'], $companyId);

            return $this->quotations->findOrFail($quotation->id);
        });
    }

    public function updateQuotation(int $id, array $data, int $companyId): Quotation
    {
        $quotation = $this->quotations->findOrFail($id);

        if ($quotation->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft quotations can be edited.']);
        }

        return DB::transaction(function () use ($quotation, $data, $companyId) {
            [$subtotal, $tax, $total] = $this->sumLines($data['lines']);

            $quotation->lines()->delete();

            $this->quotations->update($quotation, [
                'customer_id' => $data['customer_id'],
                'date'        => $data['date'],
                'valid_until' => $data['valid_until'] ?? null,
                'subtotal'    => $subtotal,
                'tax'         => $tax,
                'total'       => $total,
                'notes'       => $data['notes'] ?? null,
            ]);

            $this->createQuotationLines($quotation, $data['lines'], $companyId);

            return $this->quotations->findOrFail($quotation->id);
        });
    }

    public function convertQuotationToOrder(int $id, int $companyId): SalesOrder
    {
        $quotation = $this->quotations->findOrFail($id);

        if ($quotation->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft quotations can be converted.']);
        }

        return DB::transaction(function () use ($quotation, $companyId) {
            $this->quotations->update($quotation, ['status' => 'accepted']);

            $order = $this->orders->create([
                'company_id'   => $companyId,
                'quotation_id' => $quotation->id,
                'customer_id'  => $quotation->customer_id,
                'date'         => now()->toDateString(),
                'status'       => 'confirmed',
                'subtotal'     => $quotation->subtotal,
                'tax'          => $quotation->tax,
                'total'        => $quotation->total,
            ]);

            foreach ($quotation->lines as $line) {
                SalesOrderLine::create([
                    'company_id'     => $companyId,
                    'sales_order_id' => $order->id,
                    'product_id'     => $line->product_id,
                    'qty'            => $line->qty,
                    'unit_price'     => $line->unit_price,
                    'total'          => $line->total,
                ]);
            }

            return $this->orders->findOrFail($order->id);
        });
    }

    // ── Sales Orders ──────────────────────────────────────────────────────────

    public function listOrders(): LengthAwarePaginator
    {
        return $this->orders->paginate();
    }

    public function getOrder(int $id): SalesOrder
    {
        return $this->orders->findOrFail($id);
    }

    public function convertOrderToInvoice(int $id, int $companyId): Invoice
    {
        $order = $this->orders->findOrFail($id);

        if (!in_array($order->status, ['confirmed', 'draft'])) {
            throw ValidationException::withMessages(['status' => 'Order cannot be invoiced in its current state.']);
        }

        if ($order->invoice) {
            throw ValidationException::withMessages(['invoice' => 'This order already has an invoice.']);
        }

        return DB::transaction(function () use ($order, $companyId) {
            $invoice = $this->invoices->create([
                'company_id'     => $companyId,
                'sales_order_id' => $order->id,
                'customer_id'    => $order->customer_id,
                'invoice_number' => $this->invoices->nextInvoiceNumber(),
                'date'           => now()->toDateString(),
                'due_date'       => now()->addDays(30)->toDateString(),
                'status'         => 'draft',
                'subtotal'       => $order->subtotal,
                'tax'            => $order->tax,
                'total'          => $order->total,
                'paid_amount'    => 0,
            ]);

            foreach ($order->lines as $line) {
                InvoiceLine::create([
                    'company_id' => $companyId,
                    'invoice_id' => $invoice->id,
                    'product_id' => $line->product_id,
                    'qty'        => $line->qty,
                    'unit_price' => $line->unit_price,
                    'total'      => $line->total,
                ]);
            }

            $this->orders->update($order, ['status' => 'invoiced']);

            return $this->invoices->findOrFail($invoice->id);
        });
    }

    // ── Invoices ──────────────────────────────────────────────────────────────

    public function listInvoices(): LengthAwarePaginator
    {
        return $this->invoices->paginate();
    }

    public function getInvoice(int $id): Invoice
    {
        return $this->invoices->findOrFail($id);
    }

    public function confirmInvoice(int $id, int $warehouseId, int $companyId): Invoice
    {
        $invoice = $this->invoices->findOrFail($id);

        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft invoices can be confirmed.']);
        }

        return DB::transaction(function () use ($invoice, $warehouseId, $companyId) {
            $totalCogs = 0;

            foreach ($invoice->lines as $line) {
                $totalCogs += $this->fifoDeduct($line->product_id, $warehouseId, $line->qty, $companyId);
            }

            $this->invoices->update($invoice, ['status' => 'unpaid']);

            $this->journalForInvoice($invoice, $totalCogs, $companyId);

            Customer::where('id', $invoice->customer_id)->increment('balance', $invoice->total);

            return $this->invoices->findOrFail($invoice->id);
        });
    }

    public function recordPayment(array $data, int $companyId): PaymentReceived
    {
        $invoice = $this->invoices->findOrFail($data['invoice_id']);

        if (!in_array($invoice->status, ['unpaid', 'partial'])) {
            throw ValidationException::withMessages(['status' => 'Invoice is not open for payment.']);
        }

        $amount = $data['amount'];

        if ($amount > $invoice->balance_due) {
            throw ValidationException::withMessages(['amount' => 'Payment exceeds balance due.']);
        }

        return DB::transaction(function () use ($invoice, $data, $amount, $companyId) {
            $journal = $this->journalForPayment($invoice, $amount, $companyId);

            $payment = PaymentReceived::create([
                'company_id'       => $companyId,
                'invoice_id'       => $invoice->id,
                'amount'           => $amount,
                'date'             => $data['date'] ?? now()->toDateString(),
                'method'           => $data['method'] ?? 'cash',
                'journal_entry_id' => $journal?->id,
                'notes'            => $data['notes'] ?? null,
            ]);

            $newPaid = $invoice->paid_amount + $amount;
            $status  = $newPaid >= $invoice->total ? 'paid' : 'partial';
            $this->invoices->update($invoice, ['paid_amount' => $newPaid, 'status' => $status]);

            Customer::where('id', $invoice->customer_id)->decrement('balance', $amount);

            return $payment->load('invoice.customer');
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function sumLines(array $lines): array
    {
        $subtotal = 0;
        foreach ($lines as $line) {
            $lineTotal  = $line['qty'] * $line['unit_price'];
            $discount   = $line['discount'] ?? 0;
            $subtotal  += $lineTotal - $discount;
        }
        $tax   = 0;
        $total = $subtotal + $tax;
        return [$subtotal, $tax, $total];
    }

    private function createQuotationLines(Quotation $quotation, array $lines, int $companyId): void
    {
        foreach ($lines as $line) {
            $lineTotal = ($line['qty'] * $line['unit_price']) - ($line['discount'] ?? 0);
            QuotationLine::create([
                'company_id'   => $companyId,
                'quotation_id' => $quotation->id,
                'product_id'   => $line['product_id'],
                'qty'          => $line['qty'],
                'unit_price'   => $line['unit_price'],
                'discount'     => $line['discount'] ?? 0,
                'total'        => $lineTotal,
            ]);
        }
    }

    private function fifoDeduct(int $productId, int $warehouseId, int $qty, int $companyId): int
    {
        $remaining = $qty;
        $totalCost = 0;

        $layers = StockLayer::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('qty_remaining', '>', 0)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        foreach ($layers as $layer) {
            if ($remaining <= 0) {
                break;
            }
            $take       = min($remaining, $layer->qty_remaining);
            $totalCost += $take * $layer->cost_per_unit;
            $layer->decrement('qty_remaining', $take);
            $remaining -= $take;
        }

        $costPerUnit = $qty > 0 ? intdiv($totalCost, $qty) : 0;

        StockMovement::create([
            'company_id'    => $companyId,
            'product_id'    => $productId,
            'warehouse_id'  => $warehouseId,
            'type'          => 'out',
            'qty'           => $qty,
            'cost_per_unit' => $costPerUnit,
            'date'          => now()->toDateString(),
        ]);

        return $totalCost;
    }

    private function journalForInvoice(Invoice $invoice, int $cogs, int $companyId): void
    {
        $ar      = Account::where('company_id', $companyId)->where('code', '1020')->first();
        $revenue = Account::where('company_id', $companyId)->where('code', '4010')->first();

        if ($ar && $revenue) {
            $entry = JournalEntry::create([
                'company_id'  => $companyId,
                'date'        => now()->toDateString(),
                'reference'   => $invoice->invoice_number,
                'description' => "Invoice {$invoice->invoice_number}",
                'type'        => 'sale',
                'status'      => 'posted',
            ]);
            $entry->lines()->createMany([
                ['company_id' => $companyId, 'account_id' => $ar->id,      'debit' => $invoice->total, 'credit' => 0],
                ['company_id' => $companyId, 'account_id' => $revenue->id, 'debit' => 0, 'credit' => $invoice->total],
            ]);
        }

        if ($cogs > 0) {
            $cogsAcct  = Account::where('company_id', $companyId)->where('code', '5010')->first();
            $inventory = Account::where('company_id', $companyId)->where('code', '1030')->first();

            if ($cogsAcct && $inventory) {
                $entry = JournalEntry::create([
                    'company_id'  => $companyId,
                    'date'        => now()->toDateString(),
                    'reference'   => $invoice->invoice_number,
                    'description' => "COGS — {$invoice->invoice_number}",
                    'type'        => 'sale',
                    'status'      => 'posted',
                ]);
                $entry->lines()->createMany([
                    ['company_id' => $companyId, 'account_id' => $cogsAcct->id,  'debit' => $cogs, 'credit' => 0],
                    ['company_id' => $companyId, 'account_id' => $inventory->id, 'debit' => 0, 'credit' => $cogs],
                ]);
            }
        }
    }

    private function journalForPayment(Invoice $invoice, int $amount, int $companyId): ?JournalEntry
    {
        $cash = Account::where('company_id', $companyId)->where('code', '1010')->first();
        $ar   = Account::where('company_id', $companyId)->where('code', '1020')->first();

        if (!$cash || !$ar) {
            return null;
        }

        $entry = JournalEntry::create([
            'company_id'  => $companyId,
            'date'        => now()->toDateString(),
            'reference'   => $invoice->invoice_number,
            'description' => "Payment received — {$invoice->invoice_number}",
            'type'        => 'payment',
            'status'      => 'posted',
        ]);

        $entry->lines()->createMany([
            ['company_id' => $companyId, 'account_id' => $cash->id, 'debit' => $amount, 'credit' => 0],
            ['company_id' => $companyId, 'account_id' => $ar->id,   'debit' => 0, 'credit' => $amount],
        ]);

        return $entry;
    }
}
