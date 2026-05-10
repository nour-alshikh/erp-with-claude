'use client';

import { useParams } from 'next/navigation';
import { useProduct } from '@/lib/hooks/useInventory';
import { useStockLevels } from '@/lib/hooks/useInventory';
import { formatCents } from '@/lib/utils/money';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

export default function ProductDetailPage() {
  const { id } = useParams<{ id: string }>();
  const productId = Number(id);

  const { data: product, isLoading: loadingProduct } = useProduct(productId);
  const { data: levels, isLoading: loadingLevels } = useStockLevels();

  const productLevels = levels?.filter((l: any) => l.product?.id === productId) ?? [];

  if (loadingProduct) return <p className="text-muted-foreground">Loading…</p>;
  if (!product) return <p className="text-muted-foreground">Product not found.</p>;

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-bold mb-4">{product.name}</h1>
        <div className="border rounded-lg p-6 grid grid-cols-2 gap-4 text-sm">
          <div>
            <span className="text-muted-foreground">SKU</span>
            <p className="font-medium">{product.sku ?? '—'}</p>
          </div>
          <div>
            <span className="text-muted-foreground">Barcode</span>
            <p className="font-medium">{product.barcode ?? '—'}</p>
          </div>
          <div>
            <span className="text-muted-foreground">Unit of Measure</span>
            <p className="font-medium">{product.unit_of_measure ?? '—'}</p>
          </div>
          <div>
            <span className="text-muted-foreground">Reorder Point</span>
            <p className="font-medium">{product.reorder_point}</p>
          </div>
          <div>
            <span className="text-muted-foreground">Cost Price</span>
            <p className="font-medium">{formatCents(product.cost_price)}</p>
          </div>
          <div>
            <span className="text-muted-foreground">Selling Price</span>
            <p className="font-medium">{formatCents(product.selling_price)}</p>
          </div>
        </div>
      </div>

      <div>
        <h2 className="text-lg font-semibold mb-3">Stock by Warehouse</h2>
        {loadingLevels ? (
          <p className="text-muted-foreground">Loading stock levels…</p>
        ) : (
          <div className="border rounded-lg overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Warehouse</TableHead>
                  <TableHead>Location</TableHead>
                  <TableHead className="text-right">Qty on Hand</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {productLevels.length ? (
                  productLevels.map((l: any, i: number) => (
                    <TableRow key={i}>
                      <TableCell className="font-medium">{l.warehouse?.name}</TableCell>
                      <TableCell>{l.warehouse?.location ?? '—'}</TableCell>
                      <TableCell className="text-right">{l.qty}</TableCell>
                    </TableRow>
                  ))
                ) : (
                  <TableRow>
                    <TableCell colSpan={3} className="text-center text-muted-foreground py-6">
                      No stock recorded for this product.
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        )}
      </div>
    </div>
  );
}
