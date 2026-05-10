'use client';

import { useVendors, useDeleteVendor } from '@/lib/hooks/usePurchasing';
import { formatCents } from '@/lib/utils/money';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { toast } from 'sonner';

export default function VendorsPage() {
  const { data: vendors, isLoading } = useVendors();
  const deleteMutation = useDeleteVendor();

  function handleDelete(id: number, name: string) {
    if (!confirm(`Delete vendor "${name}"?`)) return;
    deleteMutation.mutate(id, {
      onSuccess: () => toast.success('Vendor deleted'),
      onError:   () => toast.error('Failed to delete vendor'),
    });
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold">Vendors</h1>
      </div>

      {isLoading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="border rounded-lg overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead className="text-right">AP Balance</TableHead>
                <TableHead />
              </TableRow>
            </TableHeader>
            <TableBody>
              {vendors?.length ? (
                vendors.map((v: any) => (
                  <TableRow key={v.id}>
                    <TableCell className="font-medium">{v.name}</TableCell>
                    <TableCell>{v.email ?? '—'}</TableCell>
                    <TableCell>{v.phone ?? '—'}</TableCell>
                    <TableCell className="text-right">{formatCents(v.balance)}</TableCell>
                    <TableCell className="text-right">
                      <Button
                        variant="ghost"
                        size="sm"
                        className="text-destructive hover:text-destructive"
                        onClick={() => handleDelete(v.id, v.name)}
                        disabled={deleteMutation.isPending}
                      >
                        Delete
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={5} className="text-center text-muted-foreground py-8">
                    No vendors found.
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
