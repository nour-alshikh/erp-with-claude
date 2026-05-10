'use client';

import { useGrns, useConfirmGrn } from '@/lib/hooks/usePurchasing';
import { Button } from '@/components/ui/button';
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

export default function GrnPage() {
  const { data: grns, isLoading } = useGrns();
  const confirmMutation = useConfirmGrn();

  function handleConfirm(id: number) {
    if (!confirm('Confirm this GRN? This will update inventory stock and create a vendor bill.')) return;
    confirmMutation.mutate(id, {
      onSuccess: () => toast.success('GRN confirmed — stock updated and bill created'),
      onError:   (e: any) =>
        toast.error(e?.response?.data?.message ?? 'Failed to confirm GRN'),
    });
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold">Goods Received Notes</h1>
      </div>

      {isLoading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="border rounded-lg overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>GRN #</TableHead>
                <TableHead>Purchase Order</TableHead>
                <TableHead>Vendor</TableHead>
                <TableHead>Warehouse</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Status</TableHead>
                <TableHead />
              </TableRow>
            </TableHeader>
            <TableBody>
              {grns?.data?.length || grns?.length ? (
                (grns?.data ?? grns).map((g: any) => (
                  <TableRow key={g.id}>
                    <TableCell className="font-medium">GRN-{g.id}</TableCell>
                    <TableCell>PO-{g.purchase_order_id}</TableCell>
                    <TableCell>{g.purchase_order?.vendor?.name ?? '—'}</TableCell>
                    <TableCell>{g.warehouse?.name ?? '—'}</TableCell>
                    <TableCell>{g.date}</TableCell>
                    <TableCell>
                      <Badge variant={g.status === 'confirmed' ? 'default' : 'secondary'}>
                        {g.status}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right">
                      {g.status === 'draft' && (
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleConfirm(g.id)}
                          disabled={confirmMutation.isPending}
                        >
                          Confirm
                        </Button>
                      )}
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={7} className="text-center text-muted-foreground py-8">
                    No GRNs found.
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
