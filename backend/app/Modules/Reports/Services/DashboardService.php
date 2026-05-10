<?php

namespace App\Modules\Reports\Services;

use App\Base\BaseService;
use Illuminate\Support\Facades\DB;

class DashboardService extends BaseService
{
    public function kpis(int $companyId): array
    {
        $from = now()->startOfMonth()->toDateString();
        $to   = now()->toDateString();

        $revenue = (int) (DB::table('journal_lines')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.company_id', $companyId)
            ->where('accounts.type', 'income')
            ->whereBetween('journal_entries.date', [$from, $to])
            ->selectRaw('COALESCE(SUM(journal_lines.credit) - SUM(journal_lines.debit), 0) as amount')
            ->value('amount') ?? 0);

        $expenses = (int) (DB::table('journal_lines')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.company_id', $companyId)
            ->where('accounts.type', 'expense')
            ->whereBetween('journal_entries.date', [$from, $to])
            ->selectRaw('COALESCE(SUM(journal_lines.debit) - SUM(journal_lines.credit), 0) as amount')
            ->value('amount') ?? 0);

        $outstandingAr = (int) (DB::table('invoices')
            ->where('company_id', $companyId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as amount')
            ->value('amount') ?? 0);

        $outstandingAp = (int) (DB::table('vendor_bills')
            ->where('company_id', $companyId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as amount')
            ->value('amount') ?? 0);

        return [
            'revenue_mtd'    => $revenue,
            'expenses_mtd'   => $expenses,
            'net_profit_mtd' => $revenue - $expenses,
            'outstanding_ar' => $outstandingAr,
            'outstanding_ap' => $outstandingAp,
        ];
    }

    public function revenueTrend(int $companyId): array
    {
        $since = now()->subMonths(11)->startOfMonth()->toDateString();

        $rows = DB::table('journal_lines')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.company_id', $companyId)
            ->where('accounts.type', 'income')
            ->where('journal_entries.date', '>=', $since)
            ->groupByRaw('YEAR(journal_entries.date), MONTH(journal_entries.date)')
            ->orderByRaw('YEAR(journal_entries.date) ASC, MONTH(journal_entries.date) ASC')
            ->selectRaw('
                YEAR(journal_entries.date)  as yr,
                MONTH(journal_entries.date) as mo,
                COALESCE(SUM(journal_lines.credit) - SUM(journal_lines.debit), 0) as revenue
            ')
            ->get()
            ->keyBy(fn ($r) => "{$r->yr}-{$r->mo}");

        return collect(range(11, 0))->map(function ($i) use ($rows) {
            $date = now()->subMonths($i)->startOfMonth();
            $key  = "{$date->year}-{$date->month}";
            return [
                'month'   => $date->format('M y'),
                'revenue' => isset($rows[$key]) ? (int) $rows[$key]->revenue : 0,
            ];
        })->values()->all();
    }

    public function topProducts(int $companyId): array
    {
        return DB::table('invoice_lines')
            ->join('products', 'invoice_lines.product_id', '=', 'products.id')
            ->join('invoices', 'invoice_lines.invoice_id', '=', 'invoices.id')
            ->where('invoices.company_id', $companyId)
            ->whereNotIn('invoices.status', ['draft'])
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->selectRaw('products.name as product, CAST(SUM(invoice_lines.total) AS SIGNED) as revenue')
            ->get()
            ->map(fn ($r) => ['product' => $r->product, 'revenue' => (int) $r->revenue])
            ->all();
    }

    public function topCustomers(int $companyId): array
    {
        return DB::table('invoices')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->where('invoices.company_id', $companyId)
            ->whereNotIn('invoices.status', ['draft'])
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->selectRaw('customers.name as customer, CAST(SUM(invoices.total) AS SIGNED) as revenue')
            ->get()
            ->map(fn ($r) => ['customer' => $r->customer, 'revenue' => (int) $r->revenue])
            ->all();
    }

    public function lowStock(int $companyId): array
    {
        return DB::table('stock_layers')
            ->join('products',   'stock_layers.product_id',   '=', 'products.id')
            ->join('warehouses', 'stock_layers.warehouse_id', '=', 'warehouses.id')
            ->where('stock_layers.company_id', $companyId)
            ->groupBy(
                'stock_layers.product_id',
                'stock_layers.warehouse_id',
                'products.name',
                'products.reorder_point',
                'warehouses.name',
            )
            ->havingRaw('SUM(stock_layers.qty_remaining) <= products.reorder_point')
            ->orderByRaw('SUM(stock_layers.qty_remaining) ASC')
            ->limit(10)
            ->selectRaw('
                products.name   as product_name,
                warehouses.name as warehouse_name,
                products.reorder_point,
                CAST(SUM(stock_layers.qty_remaining) AS SIGNED) as qty
            ')
            ->get()
            ->map(fn ($r) => [
                'product_name'   => $r->product_name,
                'warehouse_name' => $r->warehouse_name,
                'reorder_point'  => (int) $r->reorder_point,
                'qty'            => (int) $r->qty,
            ])
            ->all();
    }

    public function recentActivity(int $companyId): array
    {
        $invoices = DB::table('invoices')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->where('invoices.company_id', $companyId)
            ->orderByDesc('invoices.created_at')
            ->limit(5)
            ->selectRaw("
                invoices.invoice_number as reference,
                customers.name          as party,
                invoices.total          as amount,
                invoices.status,
                invoices.created_at,
                'invoice'               as type
            ")
            ->get();

        $posSales = DB::table('pos_transactions')
            ->where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->selectRaw("
                transaction_number as reference,
                'POS Sale'         as party,
                total              as amount,
                status,
                created_at,
                'pos'              as type
            ")
            ->get();

        return $invoices->concat($posSales)
            ->sortByDesc('created_at')
            ->take(10)
            ->map(fn ($r) => [
                'type'      => $r->type,
                'reference' => $r->reference,
                'party'     => $r->party,
                'amount'    => (int) $r->amount,
                'status'    => $r->status,
                'date'      => $r->created_at,
            ])
            ->values()
            ->all();
    }
}
