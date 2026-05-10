'use client';

import { useOrders, useConvertOrder } from '@/lib/hooks/useSales';
import { formatCents } from '@/lib/utils/money';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { toast } from 'sonner';

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
  confirmed: 'default',
  invoiced:  'secondary',
  draft:     'outline',
};

export default function SalesOrdersPage() {
  const { data: orders, isLoading } = useOrders();
  const convertMutation = useConvertOrder();

  function handleConvert(id: number) {
    if (!confirm('Create an invoice from this sales order?')) return;
    convertMutation.mutate(id, {
      onSuccess: () => toast.success('Invoice created'),
      onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Failed'),
    });
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Sales Orders</h1>
      </div>

      {isLoading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="border rounded-lg overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>#</TableHead>
                <TableHead>Customer</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Total</TableHead>
                <TableHead />
              </TableRow>
            </TableHeader>
            <TableBody>
              {orders?.length ? orders.map((o: any) => (
                <TableRow key={o.id}>
                  <TableCell className="font-mono text-sm">SO-{o.id}</TableCell>
                  <TableCell className="font-medium">{o.customer?.name ?? '—'}</TableCell>
                  <TableCell className="text-sm">{o.date}</TableCell>
                  <TableCell>
                    <Badge variant={statusVariant[o.status] ?? 'secondary'}>{o.status}</Badge>
                  </TableCell>
                  <TableCell className="text-right font-semibold">{formatCents(o.total)}</TableCell>
                  <TableCell>
                    {!o.invoice_id && o.status !== 'invoiced' && (
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => handleConvert(o.id)}
                        disabled={convertMutation.isPending}
                      >
                        → Invoice
                      </Button>
                    )}
                  </TableCell>
                </TableRow>
              )) : (
                <TableRow>
                  <TableCell colSpan={6} className="text-center text-muted-foreground py-8">
                    No sales orders yet. Convert a quotation to create one.
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
