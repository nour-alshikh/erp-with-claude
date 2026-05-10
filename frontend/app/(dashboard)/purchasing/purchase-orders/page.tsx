'use client';

import Link from 'next/link';
import { usePurchaseOrders, useDeletePurchaseOrder, useSendPurchaseOrder } from '@/lib/hooks/usePurchasing';
import { formatCents } from '@/lib/utils/money';
import { Button, buttonVariants } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
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
  draft:               'secondary',
  sent:                'default',
  partially_received:  'default',
  received:            'secondary',
};

export default function PurchaseOrdersPage() {
  const { data: orders, isLoading } = usePurchaseOrders();
  const deleteMutation = useDeletePurchaseOrder();
  const sendMutation   = useSendPurchaseOrder();

  function handleDelete(id: number) {
    if (!confirm('Delete this purchase order?')) return;
    deleteMutation.mutate(id, {
      onSuccess: () => toast.success('Purchase order deleted'),
      onError:   () => toast.error('Failed to delete purchase order'),
    });
  }

  function handleSend(id: number) {
    sendMutation.mutate(id, {
      onSuccess: () => toast.success('Purchase order sent'),
      onError:   () => toast.error('Failed to send purchase order'),
    });
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold">Purchase Orders</h1>
        <Link href="/purchasing/purchase-orders/new" className={buttonVariants({ size: 'sm' })}>
          New PO
        </Link>
      </div>

      {isLoading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="border rounded-lg overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>PO #</TableHead>
                <TableHead>Vendor</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Total</TableHead>
                <TableHead />
              </TableRow>
            </TableHeader>
            <TableBody>
              {orders?.data?.length || orders?.length ? (
                (orders?.data ?? orders).map((po: any) => (
                  <TableRow key={po.id}>
                    <TableCell className="font-medium">PO-{po.id}</TableCell>
                    <TableCell>{po.vendor?.name ?? '—'}</TableCell>
                    <TableCell>{po.date}</TableCell>
                    <TableCell>
                      <Badge variant={statusVariant[po.status] ?? 'secondary'}>
                        {po.status.replace('_', ' ')}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right">{formatCents(po.total)}</TableCell>
                    <TableCell className="text-right space-x-2">
                      {po.status === 'draft' && (
                        <>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleSend(po.id)}
                            disabled={sendMutation.isPending}
                          >
                            Send
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            className="text-destructive hover:text-destructive"
                            onClick={() => handleDelete(po.id)}
                            disabled={deleteMutation.isPending}
                          >
                            Delete
                          </Button>
                        </>
                      )}
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={6} className="text-center text-muted-foreground py-8">
                    No purchase orders found.
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
