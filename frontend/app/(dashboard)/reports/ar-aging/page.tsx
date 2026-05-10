'use client';

import { useArAging } from '@/lib/hooks/useReports';
import { formatCents } from '@/lib/utils/money';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';

const bucketLabels: Record<string, string> = {
  current: 'Current',
  '1_30':  '1–30 Days',
  '31_60': '31–60 Days',
  '61_90': '61–90 Days',
  over_90: '90+ Days',
};

const bucketColors: Record<string, string> = {
  current: 'bg-green-50 border-green-200 dark:bg-green-950/20',
  '1_30':  'bg-yellow-50 border-yellow-200 dark:bg-yellow-950/20',
  '31_60': 'bg-orange-50 border-orange-200 dark:bg-orange-950/20',
  '61_90': 'bg-red-50 border-red-200 dark:bg-red-950/20',
  over_90: 'bg-red-100 border-red-300 dark:bg-red-900/30',
};

const bucketTextColors: Record<string, string> = {
  current: 'text-green-700 dark:text-green-400',
  '1_30':  'text-yellow-700 dark:text-yellow-400',
  '31_60': 'text-orange-700 dark:text-orange-400',
  '61_90': 'text-red-700 dark:text-red-400',
  over_90: 'text-red-800 dark:text-red-300',
};

function agingBadge(days: number) {
  if (days <= 0)  return <Badge variant="secondary">Current</Badge>;
  if (days <= 30) return <Badge variant="outline">{days}d</Badge>;
  if (days <= 60) return <Badge className="bg-orange-500 hover:bg-orange-600">{days}d</Badge>;
  if (days <= 90) return <Badge variant="destructive">{days}d</Badge>;
  return <Badge variant="destructive">{days}d overdue</Badge>;
}

export default function ArAgingPage() {
  const { data, isLoading, refetch } = useArAging();

  return (
    <div className="space-y-6 print:space-y-4">
      <div className="flex items-center justify-between print:hidden">
        <h1 className="text-2xl font-bold">AR Aging Report</h1>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => refetch()}>Refresh</Button>
          <Button variant="outline" onClick={() => window.print()}>Print</Button>
        </div>
      </div>
      <h1 className="hidden print:block text-2xl font-bold text-center">AR Aging Report</h1>

      {isLoading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : data ? (
        <>
          {/* Bucket summary */}
          <div className="grid grid-cols-5 gap-3">
            {Object.entries(data.buckets ?? {}).map(([key, val]: [string, any]) => (
              <div key={key} className={`border rounded-xl p-3 ${bucketColors[key]}`}>
                <p className="text-xs font-medium text-muted-foreground">{bucketLabels[key]}</p>
                <p className={`text-lg font-bold mt-1 ${bucketTextColors[key]}`}>{formatCents(val)}</p>
              </div>
            ))}
          </div>

          {/* Total */}
          <div className="flex justify-between items-center border rounded-lg px-4 py-3 bg-muted/20">
            <span className="font-semibold">Total Outstanding AR</span>
            <span className="text-xl font-bold">{formatCents(data.total)}</span>
          </div>

          {/* Detail table */}
          <div className="border rounded-lg overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Customer</TableHead>
                  <TableHead>Invoice #</TableHead>
                  <TableHead>Due Date</TableHead>
                  <TableHead>Overdue</TableHead>
                  <TableHead className="text-right">Balance Due</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.rows?.length ? data.rows.map((r: any, i: number) => (
                  <TableRow key={i}>
                    <TableCell className="font-medium">{r.customer_name}</TableCell>
                    <TableCell className="font-mono text-sm">{r.invoice_number}</TableCell>
                    <TableCell className="text-sm">{r.due_date}</TableCell>
                    <TableCell>{agingBadge(r.days_overdue)}</TableCell>
                    <TableCell className="text-right font-semibold text-destructive">
                      {formatCents(r.balance_due)}
                    </TableCell>
                  </TableRow>
                )) : (
                  <TableRow>
                    <TableCell colSpan={5} className="text-center text-muted-foreground py-8">
                      No outstanding receivables.
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        </>
      ) : null}
    </div>
  );
}
