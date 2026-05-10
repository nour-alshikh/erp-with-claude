'use client';

import { useState } from 'react';
import { useInvoices, useConfirmInvoice, useCreatePayment } from '@/lib/hooks/useSales';
import { useWarehouses } from '@/lib/hooks/useInventory';
import { formatCents } from '@/lib/utils/money';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { toast } from 'sonner';

const statusVariant: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
  draft:   'outline',
  unpaid:  'destructive',
  partial: 'secondary',
  paid:    'default',
};

export default function InvoicesPage() {
  const { data: invoices,   isLoading } = useInvoices();
  const { data: warehouses }            = useWarehouses();
  const confirmMutation = useConfirmInvoice();
  const paymentMutation = useCreatePayment();

  const [confirmingId,  setConfirmingId]  = useState<number | null>(null);
  const [warehouseId,   setWarehouseId]   = useState('');
  const [payingInvoice, setPayingInvoice] = useState<any>(null);
  const [payAmount,     setPayAmount]     = useState('');
  const [payMethod,     setPayMethod]     = useState('cash');

  function handleConfirm(e: React.FormEvent) {
    e.preventDefault();
    if (!confirmingId || !warehouseId) return;
    confirmMutation.mutate(
      { id: confirmingId, data: { warehouse_id: parseInt(warehouseId) } },
      {
        onSuccess: () => {
          toast.success('Invoice confirmed — stock deducted');
          setConfirmingId(null);
          setWarehouseId('');
        },
        onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Failed'),
      },
    );
  }

  function handlePayment(e: React.FormEvent) {
    e.preventDefault();
    if (!payingInvoice) return;
    paymentMutation.mutate(
      { invoice_id: payingInvoice.id, amount: parseInt(payAmount), method: payMethod },
      {
        onSuccess: () => {
          toast.success('Payment recorded');
          setPayingInvoice(null);
          setPayAmount('');
        },
        onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Failed'),
      },
    );
  }

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Invoices</h1>

      {/* Confirm invoice modal */}
      {confirmingId && (
        <div className="border rounded-xl p-6 bg-muted/20 space-y-3">
          <h2 className="font-semibold">Confirm Invoice — Select Warehouse for Stock Deduction</h2>
          <form onSubmit={handleConfirm} className="flex items-end gap-3">
            <div>
              <label className="text-sm font-medium mb-1 block">Warehouse *</label>
              <select
                className="border rounded-md px-3 py-2 text-sm bg-background"
                value={warehouseId}
                onChange={(e) => setWarehouseId(e.target.value)}
                required
              >
                <option value="">Select warehouse…</option>
                {warehouses?.map((w: any) => (
                  <option key={w.id} value={w.id}>{w.name}</option>
                ))}
              </select>
            </div>
            <Button type="submit" disabled={!warehouseId || confirmMutation.isPending}>
              {confirmMutation.isPending ? 'Confirming…' : 'Confirm'}
            </Button>
            <Button type="button" variant="ghost" onClick={() => setConfirmingId(null)}>Cancel</Button>
          </form>
        </div>
      )}

      {/* Payment modal */}
      {payingInvoice && (
        <div className="border rounded-xl p-6 bg-muted/20 space-y-3">
          <h2 className="font-semibold">Record Payment — {payingInvoice.invoice_number}</h2>
          <p className="text-sm text-muted-foreground">
            Balance due: <span className="font-semibold text-foreground">{formatCents(payingInvoice.balance_due)}</span>
          </p>
          <form onSubmit={handlePayment} className="flex items-end gap-3">
            <div>
              <label className="text-sm font-medium mb-1 block">Amount (¢) *</label>
              <Input
                type="number"
                min="1"
                max={payingInvoice.balance_due}
                value={payAmount}
                onChange={(e) => setPayAmount(e.target.value)}
                placeholder={String(payingInvoice.balance_due)}
                className="w-40"
                required
              />
            </div>
            <div>
              <label className="text-sm font-medium mb-1 block">Method</label>
              <select
                className="border rounded-md px-3 py-2 text-sm bg-background"
                value={payMethod}
                onChange={(e) => setPayMethod(e.target.value)}
              >
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="bank_transfer">Bank Transfer</option>
              </select>
            </div>
            <Button type="submit" disabled={paymentMutation.isPending}>
              {paymentMutation.isPending ? 'Saving…' : 'Record Payment'}
            </Button>
            <Button type="button" variant="ghost" onClick={() => setPayingInvoice(null)}>Cancel</Button>
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
                <TableHead>Invoice #</TableHead>
                <TableHead>Customer</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Due</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Total</TableHead>
                <TableHead className="text-right">Balance Due</TableHead>
                <TableHead />
              </TableRow>
            </TableHeader>
            <TableBody>
              {invoices?.length ? invoices.map((inv: any) => (
                <TableRow key={inv.id}>
                  <TableCell className="font-mono text-sm">{inv.invoice_number}</TableCell>
                  <TableCell className="font-medium">{inv.customer?.name ?? '—'}</TableCell>
                  <TableCell className="text-sm">{inv.date}</TableCell>
                  <TableCell className="text-sm">{inv.due_date}</TableCell>
                  <TableCell>
                    <Badge variant={statusVariant[inv.status] ?? 'secondary'}>{inv.status}</Badge>
                  </TableCell>
                  <TableCell className="text-right">{formatCents(inv.total)}</TableCell>
                  <TableCell className="text-right font-semibold">
                    {inv.balance_due > 0
                      ? <span className="text-destructive">{formatCents(inv.balance_due)}</span>
                      : <span className="text-green-600">—</span>}
                  </TableCell>
                  <TableCell>
                    <div className="flex justify-end gap-1">
                      {inv.status === 'draft' && (
                        <Button size="sm" variant="outline" onClick={() => setConfirmingId(inv.id)}>
                          Confirm
                        </Button>
                      )}
                      {['unpaid', 'partial'].includes(inv.status) && (
                        <Button
                          size="sm"
                          variant="default"
                          onClick={() => { setPayingInvoice(inv); setPayAmount(String(inv.balance_due)); }}
                        >
                          Pay
                        </Button>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              )) : (
                <TableRow>
                  <TableCell colSpan={8} className="text-center text-muted-foreground py-8">
                    No invoices yet.
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
