<?php

namespace App\Modules\POS\Services;

use App\Base\BaseService;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Inventory\Models\StockLayer;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\POS\Models\PosPayment;
use App\Modules\POS\Models\PosTransaction;
use App\Modules\POS\Models\PosTransactionLine;
use App\Modules\POS\Repositories\Interfaces\PosSessionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosService extends BaseService
{
    public function __construct(
        private readonly PosSessionRepositoryInterface $sessions,
    ) {}

    // ── Sessions ──────────────────────────────────────────────────────────────

    public function listSessions(): LengthAwarePaginator
    {
        return $this->sessions->paginate();
    }

    public function currentSession(int $userId)
    {
        return $this->sessions->activeForUser($userId);
    }

    public function openSession(int $userId, int $companyId, int $openingFloat, int $warehouseId)
    {
        $existing = $this->sessions->activeForUser($userId);

        if ($existing) {
            throw ValidationException::withMessages([
                'session' => 'You already have an open POS session.',
            ]);
        }

        return $this->sessions->create([
            'company_id'    => $companyId,
            'warehouse_id'  => $warehouseId,
            'opened_by'     => $userId,
            'opened_at'     => now(),
            'opening_float' => $openingFloat,
            'expected_cash' => $openingFloat,
            'status'        => 'open',
        ]);
    }

    public function closeSession(int $sessionId, int $closedBy, int $actualCash)
    {
        $session = $this->sessions->findOrFail($sessionId);

        if ($session->status === 'closed') {
            throw ValidationException::withMessages(['status' => 'Session is already closed.']);
        }

        return $this->sessions->update($session, [
            'closed_by'  => $closedBy,
            'closed_at'  => now(),
            'actual_cash' => $actualCash,
            'status'     => 'closed',
        ]);
    }

    public function sessionSummary(int $sessionId): array
    {
        $session = $this->sessions->findOrFail($sessionId);

        $cashSales = PosTransaction::where('pos_session_id', $sessionId)
            ->where('status', 'completed')
            ->with('payments')
            ->get()
            ->flatMap(fn ($t) => $t->payments)
            ->where('method', 'cash')
            ->sum('amount');

        return [
            'session'       => $session,
            'opening_float' => $session->opening_float,
            'cash_sales'    => $cashSales,
            'expected_cash' => $session->expected_cash,
            'actual_cash'   => $session->actual_cash,
            'variance'      => $session->actual_cash !== null
                ? $session->actual_cash - $session->expected_cash
                : null,
        ];
    }

    // ── Transactions ──────────────────────────────────────────────────────────

    public function listTransactions(int $sessionId): \Illuminate\Database\Eloquent\Collection
    {
        return PosTransaction::where('pos_session_id', $sessionId)
            ->with(['lines.product', 'payments'])
            ->orderByDesc('date')
            ->get();
    }

    public function getTransaction(int $id): PosTransaction
    {
        return PosTransaction::with(['lines.product', 'payments', 'session'])->findOrFail($id);
    }

    public function createTransaction(array $data, int $companyId): PosTransaction
    {
        return DB::transaction(function () use ($data, $companyId) {
            $session = $this->sessions->findOrFail($data['pos_session_id']);

            if ($session->status !== 'open') {
                throw ValidationException::withMessages([
                    'pos_session_id' => 'POS session is not open.',
                ]);
            }

            $warehouseId = $session->warehouse_id;

            // Compute totals
            $subtotal = 0;
            foreach ($data['lines'] as $line) {
                $subtotal += $line['qty'] * $line['unit_price'];
            }
            $total = $subtotal; // tax = 0 for MVP

            // Validate payments cover total
            $totalPaid = (int) array_sum(array_column($data['payments'], 'amount'));
            if ($totalPaid < $total) {
                throw ValidationException::withMessages([
                    'payments' => "Total paid ({$totalPaid}¢) is less than sale total ({$total}¢).",
                ]);
            }

            $transaction = PosTransaction::create([
                'company_id'         => $companyId,
                'pos_session_id'     => $session->id,
                'transaction_number' => $this->nextTransactionNumber(),
                'date'               => now(),
                'subtotal'           => $subtotal,
                'tax'                => 0,
                'total'              => $total,
                'status'             => 'completed',
            ]);

            $totalCogs = 0;

            foreach ($data['lines'] as $line) {
                $cogs = $this->fifoDeduct($companyId, $line['product_id'], $warehouseId, $line['qty']);
                $costPerUnit = $line['qty'] > 0 ? intdiv($cogs, $line['qty']) : 0;

                PosTransactionLine::create([
                    'company_id'         => $companyId,
                    'pos_transaction_id' => $transaction->id,
                    'product_id'         => $line['product_id'],
                    'qty'                => $line['qty'],
                    'unit_price'         => $line['unit_price'],
                    'cost_per_unit'      => $costPerUnit,
                    'total'              => $line['qty'] * $line['unit_price'],
                ]);

                $totalCogs += $cogs;
            }

            $cashReceived = 0;
            foreach ($data['payments'] as $payment) {
                PosPayment::create([
                    'company_id'         => $companyId,
                    'pos_transaction_id' => $transaction->id,
                    'method'             => $payment['method'],
                    'amount'             => $payment['amount'],
                ]);
                if ($payment['method'] === 'cash') {
                    $cashReceived += $payment['amount'];
                }
            }

            // Update session expected_cash
            if ($cashReceived > 0) {
                $session->increment('expected_cash', $cashReceived);
            }

            // Journal entries
            $this->journalForSale($companyId, $total, $totalCogs, $transaction->id);

            return $transaction->load(['lines.product', 'payments']);
        });
    }

    public function voidTransaction(int $id, int $companyId): PosTransaction
    {
        return DB::transaction(function () use ($id, $companyId) {
            $transaction = $this->getTransaction($id);

            if ($transaction->status === 'voided') {
                throw ValidationException::withMessages(['status' => 'Transaction is already voided.']);
            }

            $session = $this->sessions->findOrFail($transaction->pos_session_id);

            // Restore stock (use stored cost_per_unit)
            foreach ($transaction->lines as $line) {
                StockLayer::create([
                    'company_id'    => $companyId,
                    'product_id'    => $line->product_id,
                    'warehouse_id'  => $session->warehouse_id,
                    'qty_remaining' => $line->qty,
                    'cost_per_unit' => $line->cost_per_unit,
                    'date'          => now()->toDateString(),
                ]);

                StockMovement::create([
                    'company_id'    => $companyId,
                    'product_id'    => $line->product_id,
                    'warehouse_id'  => $session->warehouse_id,
                    'type'          => 'in',
                    'qty'           => $line->qty,
                    'cost_per_unit' => $line->cost_per_unit,
                    'date'          => now()->toDateString(),
                ]);
            }

            // Reverse cash in session expected_cash
            $cashPaid = $transaction->payments->where('method', 'cash')->sum('amount');
            if ($cashPaid > 0) {
                $session->decrement('expected_cash', $cashPaid);
            }

            // Create reversing journal entry
            $this->journalForVoid($companyId, $transaction);

            $transaction->update(['status' => 'voided']);

            return $transaction->fresh(['lines.product', 'payments']);
        });
    }

    // ── FIFO helpers ──────────────────────────────────────────────────────────

    private function fifoDeduct(int $companyId, int $productId, int $warehouseId, int $qty): int
    {
        $available = (int) StockLayer::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->sum('qty_remaining');

        if ($available < $qty) {
            throw ValidationException::withMessages([
                'lines' => "Insufficient stock for product #{$productId}. Available: {$available}.",
            ]);
        }

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

        StockMovement::create([
            'company_id'    => $companyId,
            'product_id'    => $productId,
            'warehouse_id'  => $warehouseId,
            'type'          => 'out',
            'qty'           => $qty,
            'cost_per_unit' => $qty > 0 ? intdiv($totalCost, $qty) : 0,
            'date'          => now()->toDateString(),
        ]);

        return $totalCost;
    }

    // ── Transaction numbering ─────────────────────────────────────────────────

    private function nextTransactionNumber(): string
    {
        $count = PosTransaction::withTrashed()
                ->whereDate('created_at', today())
                ->count() + 1;
        return 'TXN-' . now()->format('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // ── Journal entry helpers ─────────────────────────────────────────────────

    private function journalForSale(int $companyId, int $total, int $cogs, int $txId): void
    {
        $cash      = Account::where('code', '1010')->first();
        $revenue   = Account::where('code', '4020')->first();
        $cogsAcct  = Account::where('code', '5010')->first();
        $inventory = Account::where('code', '1030')->first();

        if ($cash && $revenue) {
            $entry = JournalEntry::create([
                'company_id'  => $companyId,
                'date'        => now()->toDateString(),
                'reference'   => "POS-TXN-{$txId}",
                'description' => 'POS sale',
                'type'        => 'pos',
                'status'      => 'posted',
            ]);
            $entry->lines()->createMany([
                ['company_id' => $companyId, 'account_id' => $cash->id,    'debit' => $total, 'credit' => 0],
                ['company_id' => $companyId, 'account_id' => $revenue->id, 'debit' => 0,     'credit' => $total],
            ]);
        }

        if ($cogs > 0 && $cogsAcct && $inventory) {
            $entry = JournalEntry::create([
                'company_id'  => $companyId,
                'date'        => now()->toDateString(),
                'reference'   => "POS-COGS-{$txId}",
                'description' => 'POS cost of goods sold',
                'type'        => 'pos',
                'status'      => 'posted',
            ]);
            $entry->lines()->createMany([
                ['company_id' => $companyId, 'account_id' => $cogsAcct->id,  'debit' => $cogs, 'credit' => 0],
                ['company_id' => $companyId, 'account_id' => $inventory->id, 'debit' => 0,     'credit' => $cogs],
            ]);
        }
    }

    private function journalForVoid(int $companyId, PosTransaction $transaction): void
    {
        $cash      = Account::where('code', '1010')->first();
        $revenue   = Account::where('code', '4020')->first();
        $cogsAcct  = Account::where('code', '5010')->first();
        $inventory = Account::where('code', '1030')->first();

        $total = $transaction->total;
        $cogs  = $transaction->lines->sum(fn ($l) => $l->qty * $l->cost_per_unit);

        if ($cash && $revenue) {
            $entry = JournalEntry::create([
                'company_id'  => $companyId,
                'date'        => now()->toDateString(),
                'reference'   => "VOID-TXN-{$transaction->id}",
                'description' => "Void POS transaction {$transaction->transaction_number}",
                'type'        => 'pos',
                'status'      => 'posted',
            ]);
            $entry->lines()->createMany([
                ['company_id' => $companyId, 'account_id' => $revenue->id, 'debit' => $total, 'credit' => 0],
                ['company_id' => $companyId, 'account_id' => $cash->id,    'debit' => 0,     'credit' => $total],
            ]);
        }

        if ($cogs > 0 && $cogsAcct && $inventory) {
            $entry = JournalEntry::create([
                'company_id'  => $companyId,
                'date'        => now()->toDateString(),
                'reference'   => "VOID-COGS-{$transaction->id}",
                'description' => "Void COGS for {$transaction->transaction_number}",
                'type'        => 'pos',
                'status'      => 'posted',
            ]);
            $entry->lines()->createMany([
                ['company_id' => $companyId, 'account_id' => $inventory->id, 'debit' => $cogs, 'credit' => 0],
                ['company_id' => $companyId, 'account_id' => $cogsAcct->id,  'debit' => 0,     'credit' => $cogs],
            ]);
        }
    }
}
