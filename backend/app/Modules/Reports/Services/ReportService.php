<?php

namespace App\Modules\Reports\Services;

use App\Base\BaseService;
use Illuminate\Support\Facades\DB;

class ReportService extends BaseService
{
    // ── Trial Balance ─────────────────────────────────────────────────────────

    public function trialBalance(int $companyId, string $fromDate, string $toDate): array
    {
        $rows = DB::table('journal_lines')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.company_id', $companyId)
            ->whereBetween('journal_entries.date', [$fromDate, $toDate])
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->select([
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('SUM(journal_lines.debit)  as total_debit'),
                DB::raw('SUM(journal_lines.credit) as total_credit'),
            ])
            ->get()
            ->map(fn ($r) => [
                'code'         => $r->code,
                'name'         => $r->name,
                'type'         => $r->type,
                'total_debit'  => (int) $r->total_debit,
                'total_credit' => (int) $r->total_credit,
                'balance'      => (int) $r->total_debit - (int) $r->total_credit,
            ]);

        return [
            'from_date'    => $fromDate,
            'to_date'      => $toDate,
            'rows'         => $rows->values(),
            'total_debit'  => $rows->sum('total_debit'),
            'total_credit' => $rows->sum('total_credit'),
        ];
    }

    // ── Income Statement (P&L) ────────────────────────────────────────────────

    public function incomeStatement(int $companyId, string $fromDate, string $toDate): array
    {
        $rows = DB::table('journal_lines')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.company_id', $companyId)
            ->whereIn('accounts.type', ['income', 'expense'])
            ->whereBetween('journal_entries.date', [$fromDate, $toDate])
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->select([
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('SUM(journal_lines.debit)  as total_debit'),
                DB::raw('SUM(journal_lines.credit) as total_credit'),
            ])
            ->get()
            ->map(fn ($r) => [
                'code'         => $r->code,
                'name'         => $r->name,
                'type'         => $r->type,
                'total_debit'  => (int) $r->total_debit,
                'total_credit' => (int) $r->total_credit,
                // Income: net = credit − debit; Expense: net = debit − credit
                'net'          => $r->type === 'income'
                    ? (int) $r->total_credit - (int) $r->total_debit
                    : (int) $r->total_debit  - (int) $r->total_credit,
            ]);

        $revenue  = $rows->where('type', 'income')->sum('net');
        $expenses = $rows->where('type', 'expense')->sum('net');

        return [
            'from_date'  => $fromDate,
            'to_date'    => $toDate,
            'revenue'    => $revenue,
            'expenses'   => $expenses,
            'net_profit' => $revenue - $expenses,
            'rows'       => $rows->values(),
        ];
    }

    // ── Balance Sheet ─────────────────────────────────────────────────────────

    public function balanceSheet(int $companyId, string $asOfDate): array
    {
        $rows = DB::table('journal_lines')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.date', '<=', $asOfDate)
            ->whereIn('accounts.type', ['asset', 'liability', 'equity'])
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->select([
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('SUM(journal_lines.debit)  as total_debit'),
                DB::raw('SUM(journal_lines.credit) as total_credit'),
            ])
            ->get()
            ->map(fn ($r) => [
                'code'         => $r->code,
                'name'         => $r->name,
                'type'         => $r->type,
                'total_debit'  => (int) $r->total_debit,
                'total_credit' => (int) $r->total_credit,
                // Assets increase with debit; liabilities/equity increase with credit
                'balance'      => $r->type === 'asset'
                    ? (int) $r->total_debit  - (int) $r->total_credit
                    : (int) $r->total_credit - (int) $r->total_debit,
            ]);

        $assets      = $rows->where('type', 'asset')->sum('balance');
        $liabilities = $rows->where('type', 'liability')->sum('balance');
        $equity      = $rows->where('type', 'equity')->sum('balance');

        return [
            'as_of_date'  => $asOfDate,
            'assets'      => $assets,
            'liabilities' => $liabilities,
            'equity'      => $equity,
            'rows'        => $rows->values(),
        ];
    }

    // ── AR Aging ──────────────────────────────────────────────────────────────

    public function arAging(int $companyId): array
    {
        $rows = DB::table('invoices')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->where('invoices.company_id', $companyId)
            ->whereIn('invoices.status', ['unpaid', 'partial'])
            ->orderBy('invoices.due_date')
            ->select([
                'customers.name as customer_name',
                'invoices.invoice_number',
                'invoices.due_date',
                DB::raw('(invoices.total - invoices.paid_amount) as balance_due'),
                DB::raw('DATEDIFF(CURDATE(), invoices.due_date) as days_overdue'),
            ])
            ->get()
            ->map(fn ($r) => [
                'customer_name'  => $r->customer_name,
                'invoice_number' => $r->invoice_number,
                'due_date'       => $r->due_date,
                'balance_due'    => (int) $r->balance_due,
                'days_overdue'   => (int) $r->days_overdue,
            ]);

        $buckets = $this->buildBuckets($rows->all(), 'balance_due', 'days_overdue');

        return [
            'rows'    => $rows->values(),
            'buckets' => $buckets,
            'total'   => array_sum($buckets),
        ];
    }

    // ── AP Aging ──────────────────────────────────────────────────────────────

    public function apAging(int $companyId): array
    {
        $rows = DB::table('vendor_bills')
            ->join('vendors', 'vendor_bills.vendor_id', '=', 'vendors.id')
            ->where('vendor_bills.company_id', $companyId)
            ->whereIn('vendor_bills.status', ['unpaid', 'partial'])
            ->orderBy('vendor_bills.due_date')
            ->select([
                'vendors.name as vendor_name',
                'vendor_bills.bill_number',
                'vendor_bills.due_date',
                DB::raw('(vendor_bills.total - vendor_bills.paid_amount) as balance_due'),
                DB::raw('DATEDIFF(CURDATE(), vendor_bills.due_date) as days_overdue'),
            ])
            ->get()
            ->map(fn ($r) => [
                'vendor_name'  => $r->vendor_name,
                'bill_number'  => $r->bill_number,
                'due_date'     => $r->due_date,
                'balance_due'  => (int) $r->balance_due,
                'days_overdue' => (int) $r->days_overdue,
            ]);

        $buckets = $this->buildBuckets($rows->all(), 'balance_due', 'days_overdue');

        return [
            'rows'    => $rows->values(),
            'buckets' => $buckets,
            'total'   => array_sum($buckets),
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildBuckets(array $rows, string $amtKey, string $daysKey): array
    {
        $buckets = ['current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, 'over_90' => 0];

        foreach ($rows as $row) {
            $days = is_array($row) ? (int) $row[$daysKey] : (int) $row->$daysKey;
            $amt  = is_array($row) ? (int) $row[$amtKey]  : (int) $row->$amtKey;

            if ($days <= 0)      $buckets['current'] += $amt;
            elseif ($days <= 30) $buckets['1_30']    += $amt;
            elseif ($days <= 60) $buckets['31_60']   += $amt;
            elseif ($days <= 90) $buckets['61_90']   += $amt;
            else                 $buckets['over_90'] += $amt;
        }

        return $buckets;
    }
}
