'use client';

import { useState } from 'react';
import { useIncomeStatement } from '@/lib/hooks/useReports';
import { formatCents } from '@/lib/utils/money';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';

function today()      { return new Date().toISOString().slice(0, 10); }
function monthStart() { return new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10); }

export default function IncomeStatementPage() {
  const [from, setFrom] = useState(monthStart());
  const [to,   setTo]   = useState(today());
  const [query, setQuery] = useState({ from: monthStart(), to: today() });

  const { data, isLoading, isFetching } = useIncomeStatement(query.from, query.to);

  const incomeRows  = data?.rows?.filter((r: any) => r.type === 'income')  ?? [];
  const expenseRows = data?.rows?.filter((r: any) => r.type === 'expense') ?? [];

  return (
    <div className="space-y-6 print:space-y-4">
      <div className="flex items-center justify-between print:hidden">
        <h1 className="text-2xl font-bold">Income Statement</h1>
        <Button variant="outline" onClick={() => window.print()}>Print</Button>
      </div>
      <h1 className="hidden print:block text-2xl font-bold text-center">Income Statement</h1>

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

          {/* Summary cards */}
          <div className="grid grid-cols-3 gap-4 print:gap-2">
            <div className="border rounded-xl p-4 bg-green-50 dark:bg-green-950/20">
              <p className="text-sm text-muted-foreground">Total Revenue</p>
              <p className="text-2xl font-bold text-green-700 dark:text-green-400">{formatCents(data.revenue)}</p>
            </div>
            <div className="border rounded-xl p-4 bg-red-50 dark:bg-red-950/20">
              <p className="text-sm text-muted-foreground">Total Expenses</p>
              <p className="text-2xl font-bold text-red-700 dark:text-red-400">{formatCents(data.expenses)}</p>
            </div>
            <div className={`border rounded-xl p-4 ${data.net_profit >= 0 ? 'bg-blue-50 dark:bg-blue-950/20' : 'bg-orange-50 dark:bg-orange-950/20'}`}>
              <p className="text-sm text-muted-foreground">Net Profit</p>
              <p className={`text-2xl font-bold ${data.net_profit >= 0 ? 'text-blue-700 dark:text-blue-400' : 'text-orange-700 dark:text-orange-400'}`}>
                {data.net_profit < 0 ? '(' : ''}{formatCents(Math.abs(data.net_profit))}{data.net_profit < 0 ? ')' : ''}
              </p>
            </div>
          </div>

          {/* Revenue section */}
          <section>
            <h2 className="text-lg font-semibold mb-2">Revenue</h2>
            <div className="border rounded-lg overflow-hidden">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-24">Code</TableHead>
                    <TableHead>Account</TableHead>
                    <TableHead className="text-right">Amount</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {incomeRows.length ? incomeRows.map((r: any, i: number) => (
                    <TableRow key={i}>
                      <TableCell className="font-mono text-sm">{r.code}</TableCell>
                      <TableCell>{r.name}</TableCell>
                      <TableCell className="text-right text-green-700 font-medium">{formatCents(r.net)}</TableCell>
                    </TableRow>
                  )) : (
                    <TableRow>
                      <TableCell colSpan={3} className="text-center text-muted-foreground py-4">No revenue accounts.</TableCell>
                    </TableRow>
                  )}
                </TableBody>
                <tfoot className="border-t bg-muted/30">
                  <tr>
                    <td colSpan={2} className="px-4 py-2 font-bold text-sm">Total Revenue</td>
                    <td className="px-4 py-2 text-right font-bold text-green-700">{formatCents(data.revenue)}</td>
                  </tr>
                </tfoot>
              </Table>
            </div>
          </section>

          {/* Expense section */}
          <section>
            <h2 className="text-lg font-semibold mb-2">Expenses</h2>
            <div className="border rounded-lg overflow-hidden">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-24">Code</TableHead>
                    <TableHead>Account</TableHead>
                    <TableHead className="text-right">Amount</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {expenseRows.length ? expenseRows.map((r: any, i: number) => (
                    <TableRow key={i}>
                      <TableCell className="font-mono text-sm">{r.code}</TableCell>
                      <TableCell>{r.name}</TableCell>
                      <TableCell className="text-right text-red-700 font-medium">{formatCents(r.net)}</TableCell>
                    </TableRow>
                  )) : (
                    <TableRow>
                      <TableCell colSpan={3} className="text-center text-muted-foreground py-4">No expense accounts.</TableCell>
                    </TableRow>
                  )}
                </TableBody>
                <tfoot className="border-t bg-muted/30">
                  <tr>
                    <td colSpan={2} className="px-4 py-2 font-bold text-sm">Total Expenses</td>
                    <td className="px-4 py-2 text-right font-bold text-red-700">{formatCents(data.expenses)}</td>
                  </tr>
                </tfoot>
              </Table>
            </div>
          </section>

          {/* Net profit bar */}
          <div className="border-t pt-4 flex justify-between items-center text-xl font-bold">
            <span>Net Profit / (Loss)</span>
            <span className={data.net_profit >= 0 ? 'text-blue-700' : 'text-destructive'}>
              {data.net_profit < 0 ? '(' : ''}{formatCents(Math.abs(data.net_profit))}{data.net_profit < 0 ? ')' : ''}
            </span>
          </div>
        </>
      ) : null}
    </div>
  );
}
