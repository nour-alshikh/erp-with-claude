'use client';

import { useState } from 'react';
import { useBalanceSheet } from '@/lib/hooks/useReports';
import { formatCents } from '@/lib/utils/money';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';

function today() { return new Date().toISOString().slice(0, 10); }

function Section({ title, rows, total, color }: {
  title: string;
  rows: any[];
  total: number;
  color: 'blue' | 'red' | 'purple';
}) {
  const colors = {
    blue:   { head: 'text-blue-700 dark:text-blue-400',   total: 'text-blue-700' },
    red:    { head: 'text-red-700 dark:text-red-400',     total: 'text-red-700' },
    purple: { head: 'text-purple-700 dark:text-purple-400', total: 'text-purple-700' },
  };
  return (
    <section>
      <h2 className={`text-lg font-semibold mb-2 ${colors[color].head}`}>{title}</h2>
      <div className="border rounded-lg overflow-hidden">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-24">Code</TableHead>
              <TableHead>Account</TableHead>
              <TableHead className="text-right">Balance</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {rows.length ? rows.map((r: any, i: number) => (
              <TableRow key={i}>
                <TableCell className="font-mono text-sm">{r.code}</TableCell>
                <TableCell>{r.name}</TableCell>
                <TableCell className="text-right font-medium">{formatCents(r.balance)}</TableCell>
              </TableRow>
            )) : (
              <TableRow>
                <TableCell colSpan={3} className="text-center text-muted-foreground py-4">No entries.</TableCell>
              </TableRow>
            )}
          </TableBody>
          <tfoot className="border-t bg-muted/30">
            <tr>
              <td colSpan={2} className="px-4 py-2 font-bold text-sm">Total {title}</td>
              <td className={`px-4 py-2 text-right font-bold ${colors[color].total}`}>{formatCents(total)}</td>
            </tr>
          </tfoot>
        </Table>
      </div>
    </section>
  );
}

export default function BalanceSheetPage() {
  const [asOf,  setAsOf]  = useState(today());
  const [query, setQuery] = useState(today());

  const { data, isLoading, isFetching } = useBalanceSheet(query);

  const assetRows     = data?.rows?.filter((r: any) => r.type === 'asset')     ?? [];
  const liabilityRows = data?.rows?.filter((r: any) => r.type === 'liability') ?? [];
  const equityRows    = data?.rows?.filter((r: any) => r.type === 'equity')    ?? [];

  const isBalanced = data && data.assets === data.liabilities + data.equity;

  return (
    <div className="space-y-6 print:space-y-4">
      <div className="flex items-center justify-between print:hidden">
        <h1 className="text-2xl font-bold">Balance Sheet</h1>
        <Button variant="outline" onClick={() => window.print()}>Print</Button>
      </div>
      <h1 className="hidden print:block text-2xl font-bold text-center">Balance Sheet</h1>

      {/* Filters */}
      <div className="flex items-end gap-3 print:hidden">
        <div>
          <label className="text-sm font-medium mb-1 block">As of Date</label>
          <Input type="date" value={asOf} onChange={(e) => setAsOf(e.target.value)} className="w-40" />
        </div>
        <Button onClick={() => setQuery(asOf)} disabled={isFetching}>
          {isFetching ? 'Loading…' : 'Run Report'}
        </Button>
      </div>

      {isLoading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : data ? (
        <>
          <p className="text-sm text-muted-foreground print:text-center">As of: {data.as_of_date}</p>

          {/* Summary */}
          <div className="grid grid-cols-3 gap-4">
            <div className="border rounded-xl p-4 bg-blue-50 dark:bg-blue-950/20">
              <p className="text-sm text-muted-foreground">Total Assets</p>
              <p className="text-2xl font-bold text-blue-700 dark:text-blue-400">{formatCents(data.assets)}</p>
            </div>
            <div className="border rounded-xl p-4 bg-red-50 dark:bg-red-950/20">
              <p className="text-sm text-muted-foreground">Total Liabilities</p>
              <p className="text-2xl font-bold text-red-700 dark:text-red-400">{formatCents(data.liabilities)}</p>
            </div>
            <div className="border rounded-xl p-4 bg-purple-50 dark:bg-purple-950/20">
              <p className="text-sm text-muted-foreground">Total Equity</p>
              <p className="text-2xl font-bold text-purple-700 dark:text-purple-400">{formatCents(data.equity)}</p>
            </div>
          </div>

          {/* Accounting equation check */}
          <div className={`flex items-center gap-2 text-sm px-4 py-2 rounded-lg border ${isBalanced ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'}`}>
            <span className="font-mono">Assets ({formatCents(data.assets)}) = Liabilities ({formatCents(data.liabilities)}) + Equity ({formatCents(data.equity)})</span>
            <span className="ml-auto font-bold">{isBalanced ? '✓ Balanced' : '✗ Out of balance'}</span>
          </div>

          <Section title="Assets"      rows={assetRows}     total={data.assets}      color="blue"   />
          <Section title="Liabilities" rows={liabilityRows} total={data.liabilities} color="red"    />
          <Section title="Equity"      rows={equityRows}    total={data.equity}      color="purple" />
        </>
      ) : null}
    </div>
  );
}
