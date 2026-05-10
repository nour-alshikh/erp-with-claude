'use client';

import Link from 'next/link';
import { useQuotations, useConvertQuotation } from '@/lib/hooks/useSales';
import { formatCents } from '@/lib/utils/money';
import { Button, buttonVariants } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { toast } from 'sonner';

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
  draft:    'secondary',
  accepted: 'default',
  sent:     'outline',
};

export default function QuotationsPage() {
  const { data: quotations, isLoading } = useQuotations();
  const convertMutation = useConvertQuotation();

  function handleConvert(id: number) {
    if (!confirm('Convert this quotation to a Sales Order?')) return;
    convertMutation.mutate(id, {
      onSuccess: () => toast.success('Sales order created'),
      onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Failed'),
    });
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Quotations</h1>
        <Link href="/sales/quotations/new" className={buttonVariants()}>
          + New Quotation
        </Link>
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
                <TableHead>Valid Until</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Total</TableHead>
                <TableHead />
              </TableRow>
            </TableHeader>
            <TableBody>
              {quotations?.length ? quotations.map((q: any) => (
                <TableRow key={q.id}>
                  <TableCell className="font-mono text-sm">{q.id}</TableCell>
                  <TableCell className="font-medium">{q.customer?.name ?? '—'}</TableCell>
                  <TableCell className="text-sm">{q.date}</TableCell>
                  <TableCell className="text-sm">{q.valid_until ?? '—'}</TableCell>
                  <TableCell>
                    <Badge variant={statusVariant[q.status] ?? 'secondary'}>{q.status}</Badge>
                  </TableCell>
                  <TableCell className="text-right font-semibold">{formatCents(q.total)}</TableCell>
                  <TableCell>
                    {q.status === 'draft' && (
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => handleConvert(q.id)}
                        disabled={convertMutation.isPending}
                      >
                        → Order
                      </Button>
                    )}
                  </TableCell>
                </TableRow>
              )) : (
                <TableRow>
                  <TableCell colSpan={7} className="text-center text-muted-foreground py-8">
                    No quotations yet.
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
