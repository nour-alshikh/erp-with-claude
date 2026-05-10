'use client';

import { useState } from 'react';
import { useTrialBalance } from '@/lib/hooks/useReports';
import { formatCents } from '@/lib/utils/money';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';

function today()      { return new Date().toISOString().slice(0, 10); }
function monthStart() { return new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10); }

export default function TrialBalancePage() {
  const [from, setFrom] = useState(monthStart());
  const [to,   setTo]   = useState(today());
  const [query, setQuery] = useState({ from: monthStart(), to: today() });

  const { data, isLoading, isFetching } = useTrialBalance(query.from, query.to);

  const typeLabel: Record<string, string> = {
    asset: 'Asset', liability: 'Liability', equity: 'Equity',
    income: 'Income', expense: 'Expense',
  };

  return (
    <div className="space-y-6 print:space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between print:hidden">
        <h1 className="text-2xl font-bold">Trial Balance</h1>
        <Button variant="outline" onClick={() => window.print()}>Print</Button>
      </div>
      <h1 className="hidden print:block text-2xl font-bold text-center">Trial Balance</h1>

      {/* Filters */}
      <div className="flex items-end gap-3 print:hidden">
        <div>
          <label className="text-sm font-medium mb-1 block">From</label>
          <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="w-40" />
        </div>
        <div>
          <label className="text-sm font-medium mb-1 block">To</label>
          <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} className="w-40" />
        </div>
        <Button onClick={() => setQuery({ from, to })} disabled={isFetching}>
          {isFetching ? 'Loading…' : 'Run Report'}
        </Button>
      </div>

      {isLoading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : data ? (
        <>
          <p className="text-sm text-muted-foreground print:text-center">
            Period: {data.from_date} to {data.to_date}
          </p>
          <div className="border rounded-lg overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-24">Code</TableHead>
                  <TableHead>Account Name</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead className="text-right">Debit</TableHead>
                  <TableHead className="text-right">Credit</TableHead>
                  <TableHead className="text-right">Balance</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.rows?.length ? data.rows.map((r: any, i: number) => (
                  <TableRow key={i}>
                    <TableCell className="font-mono text-sm">{r.code}</TableCell>
                    <TableCell>{r.name}</TableCell>
                    <TableCell className="text-sm text-muted-foreground capitalize">{typeLabel[r.type] ?? r.type}</TableCell>
                    <TableCell className="text-right">{r.total_debit ? formatCents(r.total_debit) : '—'}</TableCell>
                    <TableCell className="text-right">{r.total_credit ? formatCents(r.total_credit) : '—'}</TableCell>
                    <TableCell className={`text-right font-medium ${r.balance < 0 ? 'text-destructive' : ''}`}>
                      {formatCents(Math.abs(r.balance))}{r.balance < 0 ? ' Cr' : ''}
                    </TableCell>
                  </TableRow>
                )) : (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center text-muted-foreground py-8">
                      No posted journal entries in this period.
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
              {data.rows?.length > 0 && (
                <tfoot className="border-t bg-muted/30">
                  <tr>
                    <td colSpan={3} className="px-4 py-3 font-bold text-sm">Totals</td>
                    <td className="px-4 py-3 text-right font-bold">{formatCents(data.total_debit)}</td>
                    <td className="px-4 py-3 text-right font-bold">{formatCents(data.total_credit)}</td>
                    <td className={`px-4 py-3 text-right font-bold ${data.total_debit !== data.total_credit ? 'text-destructive' : 'text-green-600'}`}>
                      {data.total_debit === data.total_credit ? 'Balanced ✓' : `Off by ${formatCents(Math.abs(data.total_debit - data.total_credit))}`}
                    </td>
                  </tr>
                </tfoot>
              )}
            </Table>
          </div>
        </>
      ) : null}
    </div>
  );
}
