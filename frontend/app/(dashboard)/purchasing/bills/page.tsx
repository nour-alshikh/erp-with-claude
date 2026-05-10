'use client';

import { useState } from 'react';
import { useVendorBills, usePayBill } from '@/lib/hooks/usePurchasing';
import { formatCents } from '@/lib/utils/money';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { toast } from 'sonner';

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive'> = {
  unpaid:  'destructive',
  partial: 'default',
  paid:    'secondary',
};

interface PayForm {
  billId: number;
  amount: string;
  date: string;
  method: 'cash' | 'bank' | 'card';
}

export default function BillsPage() {
  const { data: bills, isLoading } = useVendorBills();
  const payMutation = usePayBill();
  const [payForm, setPayForm] = useState<PayForm | null>(null);

  function openPayForm(bill: any) {
    setPayForm({
      billId: bill.id,
      amount: String(bill.balance_due),
      date:   new Date().toISOString().slice(0, 10),
      method: 'bank',
    });
  }

  function handlePay(e: React.FormEvent) {
    e.preventDefault();
    if (!payForm) return;

    payMutation.mutate(
      {
        vendor_bill_id: payForm.billId,
        amount:         parseInt(payForm.amount),
        date:           payForm.date,
        method:         payForm.method,
      },
      {
        onSuccess: () => {
          toast.success('Payment recorded');
          setPayForm(null);
        },
        onError: (e: any) =>
          toast.error(e?.response?.data?.message ?? 'Payment failed'),
      },
    );
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold">Vendor Bills</h1>
      </div>

      {payForm && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <form
            onSubmit={handlePay}
            className="bg-background border rounded-xl p-6 w-96 space-y-4 shadow-xl"
          >
            <h2 className="text-lg font-semibold">Record Payment</h2>
            <div>
              <label className="text-sm font-medium mb-1 block">Amount (¢)</label>
              <Input
                type="number"
                min="1"
                value={payForm.amount}
                onChange={(e) => setPayForm({ ...payForm, amount: e.target.value })}
                required
              />
            </div>
            <div>
              <label className="text-sm font-medium mb-1 block">Date</label>
              <Input
                type="date"
                value={payForm.date}
                onChange={(e) => setPayForm({ ...payForm, date: e.target.value })}
                required
              />
            </div>
            <div>
              <label className="text-sm font-medium mb-1 block">Method</label>
              <select
                className="w-full border rounded-md px-3 py-2 text-sm bg-background"
                value={payForm.method}
                onChange={(e) => setPayForm({ ...payForm, method: e.target.value as PayForm['method'] })}
              >
                <option value="bank">Bank Transfer</option>
                <option value="cash">Cash</option>
                <option value="card">Card</option>
              </select>
            </div>
            <div className="flex gap-3">
              <Button type="submit" disabled={payMutation.isPending}>
                {payMutation.isPending ? 'Processing…' : 'Record Payment'}
              </Button>
              <Button type="button" variant="ghost" onClick={() => setPayForm(null)}>
                Cancel
              </Button>
            </div>
          </form>
        </div>
      )}

      {isLoading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="border rounded-lg overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Bill #</TableHead>
                <TableHead>Vendor</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Total</TableHead>
                <TableHead className="text-right">Paid</TableHead>
                <TableHead className="text-right">Balance Due</TableHead>
                <TableHead />
              </TableRow>
            </TableHeader>
            <TableBody>
              {bills?.data?.length || bills?.length ? (
                (bills?.data ?? bills).map((b: any) => (
                  <TableRow key={b.id}>
                    <TableCell className="font-medium">{b.bill_number ?? `BILL-${b.id}`}</TableCell>
                    <TableCell>{b.vendor?.name ?? '—'}</TableCell>
                    <TableCell>{b.date}</TableCell>
                    <TableCell>
                      <Badge variant={statusVariant[b.status] ?? 'secondary'}>
                        {b.status}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right">{formatCents(b.total)}</TableCell>
                    <TableCell className="text-right">{formatCents(b.paid_amount)}</TableCell>
                    <TableCell className="text-right font-medium">{formatCents(b.balance_due)}</TableCell>
                    <TableCell className="text-right">
                      {b.status !== 'paid' && (
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => openPayForm(b)}
                        >
                          Pay
                        </Button>
                      )}
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={8} className="text-center text-muted-foreground py-8">
                    No vendor bills found.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </div>
      )}
    </div>
  );
}
