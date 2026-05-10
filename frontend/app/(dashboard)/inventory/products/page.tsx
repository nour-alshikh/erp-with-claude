'use client';

import Link from 'next/link';
import { useProducts, useDeleteProduct } from '@/lib/hooks/useInventory';
import { formatCents } from '@/lib/utils/money';
import { Button, buttonVariants } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { toast } from 'sonner';

export default function ProductsPage() {
  const { data: products, isLoading } = useProducts();
  const deleteMutation = useDeleteProduct();

  function handleDelete(id: number, name: string) {
    if (!confirm(`Delete ${name}?`)) return;
    deleteMutation.mutate(id, {
      onSuccess: () => toast.success('Product deleted'),
      onError:   () => toast.error('Failed to delete product'),
    });
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold">Products</h1>
      </div>

      {isLoading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="border rounded-lg overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>SKU</TableHead>
                <TableHead>UOM</TableHead>
                <TableHead>Cost Price</TableHead>
                <TableHead>Selling Price</TableHead>
                <TableHead>Reorder Point</TableHead>
                <TableHead />
              </TableRow>
            </TableHeader>
            <TableBody>
              {products?.length ? (
                products.map((p: any) => (
                  <TableRow key={p.id}>
                    <TableCell className="font-medium">
                      <Link href={`/inventory/products/${p.id}`} className="hover:underline">
                        {p.name}
                      </Link>
                    </TableCell>
                    <TableCell>{p.sku ?? '—'}</TableCell>
                    <TableCell>{p.unit_of_measure ?? '—'}</TableCell>
                    <TableCell>{formatCents(p.cost_price)}</TableCell>
                    <TableCell>{formatCents(p.selling_price)}</TableCell>
                    <TableCell>{p.reorder_point}</TableCell>
                    <TableCell className="text-right space-x-2">
                      <Link
                        href={`/inventory/products/${p.id}`}
                        className={buttonVariants({ variant: 'ghost', size: 'sm' })}
                      >
                        View
                      </Link>
                      <Button
                        variant="ghost"
                        size="sm"
                        className="text-destructive hover:text-destructive"
                        onClick={() => handleDelete(p.id, p.name)}
                        disabled={deleteMutation.isPending}
                      >
                        Delete
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={7} className="text-center text-muted-foreground py-8">
                    No products found.
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
