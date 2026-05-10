'use client';

import { useWarehouses, useDeleteWarehouse, useStockLevels } from '@/lib/hooks/useInventory';
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

export default function WarehousesPage() {
  const { data: warehouses, isLoading } = useWarehouses();
  const { data: levels } = useStockLevels();
  const deleteMutation = useDeleteWarehouse();

  function totalQty(warehouseId: number): number {
    if (!levels) return 0;
    return levels
      .filter((l: any) => l.warehouse?.id === warehouseId)
      .reduce((sum: number, l: any) => sum + l.qty, 0);
  }

  function handleDelete(id: number, name: string) {
    if (!confirm(`Delete warehouse "${name}"? This cannot be undone.`)) return;
    deleteMutation.mutate(id, {
      onSuccess: () => toast.success('Warehouse deleted'),
      onError:   () => toast.error('Failed to delete warehouse'),
    });
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold">Warehouses</h1>
      </div>

      {isLoading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="border rounded-lg overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Location</TableHead>
                <TableHead className="text-right">Total Units</TableHead>
                <TableHead />
              </TableRow>
            </TableHeader>
            <TableBody>
              {warehouses?.length ? (
                warehouses.map((w: any) => (
                  <TableRow key={w.id}>
                    <TableCell className="font-medium">{w.name}</TableCell>
                    <TableCell>{w.location ?? '—'}</TableCell>
                    <TableCell className="text-right">{totalQty(w.id)}</TableCell>
                    <TableCell className="text-right">
                      <Button
                        variant="ghost"
                        size="sm"
                        className="text-destructive hover:text-destructive"
                        onClick={() => handleDelete(w.id, w.name)}
                        disabled={deleteMutation.isPending}
                      >
                        Delete
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={4} className="text-center text-muted-foreground py-8">
                    No warehouses found.
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
