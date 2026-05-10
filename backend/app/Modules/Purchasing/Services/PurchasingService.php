<?php

namespace App\Modules\Purchasing\Services;

use App\Base\BaseService;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Inventory\Models\StockLayer;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Purchasing\Models\GoodsReceivedNote;
use App\Modules\Purchasing\Models\GrnLine;
use App\Modules\Purchasing\Models\PaymentMade;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseOrderLine;
use App\Modules\Purchasing\Models\VendorBill;
use App\Modules\Purchasing\Repositories\Interfaces\GrnRepositoryInterface;
use App\Modules\Purchasing\Repositories\Interfaces\PurchaseOrderRepositoryInterface;
use App\Modules\Purchasing\Repositories\Interfaces\VendorBillRepositoryInterface;
use App\Modules\Purchasing\Repositories\Interfaces\VendorRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchasingService extends BaseService
{
    public function __construct(
        private readonly VendorRepositoryInterface       $vendors,
        private readonly PurchaseOrderRepositoryInterface $pos,
        private readonly GrnRepositoryInterface          $grns,
        private readonly VendorBillRepositoryInterface   $bills,
    ) {}

    // ── Vendors ───────────────────────────────────────────────────────────────

    public function listVendors(): Collection
    {
        return $this->vendors->all();
    }

    public function getVendor(int $id)
    {
        return $this->vendors->findOrFail($id);
    }

    public function createVendor(array $data)
    {
        return $this->vendors->create($data);
    }

    public function updateVendor(int $id, array $data)
    {
        return $this->vendors->update($this->vendors->findOrFail($id), $data);
    }

    public function deleteVendor(int $id): void
    {
        $this->vendors->delete($this->vendors->findOrFail($id));
    }

    // ── Purchase Orders ───────────────────────────────────────────────────────

    public function listOrders(): LengthAwarePaginator
    {
        return $this->pos->paginate();
    }

    public function getOrder(int $id)
    {
        return $this->pos->findOrFail($id);
    }

    public function createOrder(array $data, int $companyId): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $companyId) {
            $po = $this->pos->create([
                'company_id' => $companyId,
                'vendor_id'  => $data['vendor_id'],
                'date'       => $data['date'],
                'status'     => 'draft',
                'total'      => 0,
                'notes'      => $data['notes'] ?? null,
            ]);

            $total = 0;
            foreach ($data['lines'] as $line) {
                $lineTotal = $line['qty'] * $line['unit_cost'];
                PurchaseOrderLine::create([
                    'company_id'        => $companyId,
                    'purchase_order_id' => $po->id,
                    'product_id'        => $line['product_id'],
                    'qty'               => $line['qty'],
                    'unit_cost'         => $line['unit_cost'],
                    'total'             => $lineTotal,
                ]);
                $total += $lineTotal;
            }

            $po->update(['total' => $total]);

            return $this->pos->findOrFail($po->id);
        });
    }

    public function updateOrder(int $id, array $data): PurchaseOrder
    {
        $po = $this->pos->findOrFail($id);

        if ($po->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft purchase orders can be edited.']);
        }

        return $this->pos->update($po, $data);
    }

    public function deleteOrder(int $id): void
    {
        $po = $this->pos->findOrFail($id);

        if ($po->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft purchase orders can be deleted.']);
        }

        $this->pos->delete($po);
    }

    public function sendOrder(int $id): PurchaseOrder
    {
        $po = $this->pos->findOrFail($id);

        if ($po->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft purchase orders can be sent.']);
        }

        return $this->pos->update($po, ['status' => 'sent']);
    }

    // ── GRNs ──────────────────────────────────────────────────────────────────

    public function listGrns(): LengthAwarePaginator
    {
        return $this->grns->paginate();
    }

    public function getGrn(int $id)
    {
        return $this->grns->findOrFail($id);
    }

    public function createGrn(array $data, int $companyId): GoodsReceivedNote
    {
        return DB::transaction(function () use ($data, $companyId) {
            $grn = $this->grns->create([
                'company_id'        => $companyId,
                'purchase_order_id' => $data['purchase_order_id'],
                'warehouse_id'      => $data['warehouse_id'],
                'date'              => $data['date'],
                'status'            => 'draft',
                'notes'             => $data['notes'] ?? null,
            ]);

            // Build qty_ordered map from the PO lines
            $po = PurchaseOrder::with('lines')->findOrFail($data['purchase_order_id']);
            $poQtyMap = $po->lines->keyBy('product_id')->map(fn ($l) => $l->qty);

            foreach ($data['lines'] as $line) {
                GrnLine::create([
                    'company_id'   => $companyId,
                    'grn_id'       => $grn->id,
                    'product_id'   => $line['product_id'],
                    'qty_ordered'  => $poQtyMap[$line['product_id']] ?? $line['qty_received'],
                    'qty_received' => $line['qty_received'],
                    'unit_cost'    => $line['unit_cost'],
                ]);
            }

            return $this->grns->findOrFail($grn->id);
        });
    }

    public function deleteGrn(int $id): void
    {
        $grn = $this->grns->findOrFail($id);

        if ($grn->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft GRNs can be deleted.']);
        }

        $this->grns->delete($grn);
    }

    public function confirmGrn(int $id, int $companyId): GoodsReceivedNote
    {
        return DB::transaction(function () use ($id, $companyId) {
            $grn = $this->grns->findOrFail($id);

            if ($grn->status === 'confirmed') {
                throw ValidationException::withMessages(['status' => 'GRN is already confirmed.']);
            }

            $total = 0;
            $date  = $grn->date->toDateString();

            foreach ($grn->lines as $line) {
                StockMovement::create([
                    'company_id'    => $companyId,
                    'product_id'    => $line->product_id,
                    'warehouse_id'  => $grn->warehouse_id,
                    'type'          => 'in',
                    'qty'           => $line->qty_received,
                    'cost_per_unit' => $line->unit_cost,
                    'date'          => $date,
                ]);

                StockLayer::create([
                    'company_id'    => $companyId,
                    'product_id'    => $line->product_id,
                    'warehouse_id'  => $grn->warehouse_id,
                    'qty_remaining' => $line->qty_received,
                    'cost_per_unit' => $line->unit_cost,
                    'date'          => $date,
                ]);

                $total += $line->qty_received * $line->unit_cost;
            }

            $this->grns->update($grn, ['status' => 'confirmed']);

            $vendorId = $grn->purchaseOrder->vendor_id;

            // Auto-create vendor bill
            $bill = VendorBill::create([
                'company_id'  => $companyId,
                'vendor_id'   => $vendorId,
                'grn_id'      => $grn->id,
                'bill_number' => $this->bills->nextBillNumber(),
                'date'        => $date,
                'status'      => 'unpaid',
                'total'       => $total,
                'paid_amount' => 0,
            ]);

            // Increase vendor AP balance
            $grn->purchaseOrder->vendor->increment('balance', $total);

            // Journal entry: DR Inventory / CR Accounts Payable
            $this->journalForGrn($companyId, $total, $grn->id, $date);

            return $this->grns->findOrFail($grn->id);
        });
    }

    // ── Bills & Payments ─────────────────────────────────────────────────────

    public function listBills(): LengthAwarePaginator
    {
        return $this->bills->paginate();
    }

    public function getBill(int $id)
    {
        return $this->bills->findOrFail($id);
    }

    public function payBill(array $data, int $companyId): PaymentMade
    {
        return DB::transaction(function () use ($data, $companyId) {
            $bill = $this->bills->findOrFail($data['vendor_bill_id']);

            if ($bill->status === 'paid') {
                throw ValidationException::withMessages(['vendor_bill_id' => 'Bill is already fully paid.']);
            }

            $balanceDue = $bill->total - $bill->paid_amount;

            if ($data['amount'] > $balanceDue) {
                throw ValidationException::withMessages([
                    'amount' => "Payment ({$data['amount']}) exceeds balance due ({$balanceDue}).",
                ]);
            }

            // Journal entry: DR Accounts Payable / CR Cash
            $entry = $this->journalForPayment($companyId, $data['amount'], $bill->id, $data['date']);

            $payment = PaymentMade::create([
                'company_id'       => $companyId,
                'vendor_bill_id'   => $bill->id,
                'amount'           => $data['amount'],
                'date'             => $data['date'],
                'method'           => $data['method'] ?? 'bank',
                'journal_entry_id' => $entry?->id,
                'notes'            => $data['notes'] ?? null,
            ]);

            $newPaid   = $bill->paid_amount + $data['amount'];
            $newStatus = $newPaid >= $bill->total ? 'paid' : 'partial';
            $bill->update(['paid_amount' => $newPaid, 'status' => $newStatus]);

            // Decrease vendor AP balance
            $bill->vendor->decrement('balance', $data['amount']);

            return $payment->load(['vendorBill.vendor']);
        });
    }

    // ── Journal entry helpers ─────────────────────────────────────────────────

    private function journalForGrn(int $companyId, int $amount, int $grnId, string $date): ?JournalEntry
    {
        $inventory = Account::where('code', '1030')->first();
        $ap        = Account::where('code', '2010')->first();

        if (!$inventory || !$ap) {
            return null;
        }

        $entry = JournalEntry::create([
            'company_id'  => $companyId,
            'date'        => $date,
            'reference'   => "GRN-{$grnId}",
            'description' => 'Goods received — inventory purchased on account',
            'type'        => 'purchase',
            'status'      => 'posted',
        ]);

        $entry->lines()->createMany([
            ['company_id' => $companyId, 'account_id' => $inventory->id, 'debit' => $amount,  'credit' => 0],
            ['company_id' => $companyId, 'account_id' => $ap->id,        'debit' => 0,        'credit' => $amount],
        ]);

        return $entry;
    }

    private function journalForPayment(int $companyId, int $amount, int $billId, string $date): ?JournalEntry
    {
        $ap   = Account::where('code', '2010')->first();
        $cash = Account::where('code', '1010')->first();

        if (!$ap || !$cash) {
            return null;
        }

        $entry = JournalEntry::create([
            'company_id'  => $companyId,
            'date'        => $date,
            'reference'   => "BILL-PAY-{$billId}",
            'description' => 'Vendor bill payment',
            'type'        => 'payment',
            'status'      => 'posted',
        ]);

        $entry->lines()->createMany([
            ['company_id' => $companyId, 'account_id' => $ap->id,   'debit' => $amount, 'credit' => 0],
            ['company_id' => $companyId, 'account_id' => $cash->id, 'debit' => 0,       'credit' => $amount],
        ]);

        return $entry;
    }
}
