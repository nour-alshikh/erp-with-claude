'use client';

import { useState, useMemo } from 'react';
import { useRouter } from 'next/navigation';
import { useVendors } from '@/lib/hooks/usePurchasing';
import { useProducts } from '@/lib/hooks/useInventory';
import { useCreatePurchaseOrder } from '@/lib/hooks/usePurchasing';
import { formatCents } from '@/lib/utils/money';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { toast } from 'sonner';

interface LineItem {
  product_id: string;
  qty: string;
  unit_cost: string;
}

const emptyLine = (): LineItem => ({ product_id: '', qty: '', unit_cost: '' });

export default function NewPurchaseOrderPage() {
  const router = useRouter();
  const { data: vendors } = useVendors();
  const { data: products } = useProducts();
  const createMutation = useCreatePurchaseOrder();

  const [vendorId, setVendorId] = useState('');
  const [date, setDate]         = useState(new Date().toISOString().slice(0, 10));
  const [notes, setNotes]       = useState('');
  const [lines, setLines]       = useState<LineItem[]>([emptyLine()]);

  const total = useMemo(
    () => lines.reduce((sum, l) => sum + (parseInt(l.qty) || 0) * (parseInt(l.unit_cost) || 0), 0),
    [lines],
  );

  function updateLine(index: number, field: keyof LineItem, value: string) {
    setLines((prev) => prev.map((l, i) => (i === index ? { ...l, [field]: value } : l)));
  }

  function addLine() {
    setLines((prev) => [...prev, emptyLine()]);
  }

  function removeLine(index: number) {
    setLines((prev) => prev.filter((_, i) => i !== index));
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();

    const validLines = lines.filter((l) => l.product_id && parseInt(l.qty) > 0);
    if (!vendorId || validLines.length === 0) {
      toast.error('Select a vendor and add at least one product line.');
      return;
    }

    createMutation.mutate(
      {
        vendor_id: Number(vendorId),
        date,
        notes: notes || undefined,
        lines: validLines.map((l) => ({
          product_id: Number(l.product_id),
          qty:        parseInt(l.qty),
          unit_cost:  parseInt(l.unit_cost) || 0,
        })),
      },
      {
        onSuccess: () => {
          toast.success('Purchase order created');
          router.push('/purchasing/purchase-orders');
        },
        onError: () => toast.error('Failed to create purchase order'),
      },
    );
  }

  return (
    <div className="max-w-3xl">
      <h1 className="text-2xl font-bold mb-6">New Purchase Order</h1>

      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="text-sm font-medium mb-1 block">Vendor</label>
            <select
              className="w-full border rounded-md px-3 py-2 text-sm bg-background"
              value={vendorId}
              onChange={(e) => setVendorId(e.target.value)}
              required
            >
              <option value="">Select vendor…</option>
              {vendors?.map((v: any) => (
                <option key={v.id} value={v.id}>{v.name}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="text-sm font-medium mb-1 block">Date</label>
            <Input
              type="date"
              value={date}
              onChange={(e) => setDate(e.target.value)}
              required
            />
          </div>
        </div>

        <div>
          <label className="text-sm font-medium mb-1 block">Notes</label>
          <Input
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            placeholder="Optional notes…"
          />
        </div>

        <div>
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm font-medium">Lines</span>
            <Button type="button" variant="ghost" size="sm" onClick={addLine}>
              + Add Line
            </Button>
          </div>

          <div className="border rounded-lg overflow-hidden">
            <table className="w-full text-sm">
              <thead className="border-b bg-muted/40">
                <tr>
                  <th className="px-3 py-2 text-left font-medium">Product</th>
                  <th className="px-3 py-2 text-left font-medium w-24">Qty</th>
                  <th className="px-3 py-2 text-left font-medium w-32">Unit Cost (¢)</th>
                  <th className="px-3 py-2 text-right font-medium w-28">Line Total</th>
                  <th className="w-10" />
                </tr>
              </thead>
              <tbody>
                {lines.map((line, i) => (
                  <tr key={i} className="border-b last:border-0">
                    <td className="px-3 py-2">
                      <select
                        className="w-full border rounded px-2 py-1 text-sm bg-background"
                        value={line.product_id}
                        onChange={(e) => updateLine(i, 'product_id', e.target.value)}
                      >
                        <option value="">Select product…</option>
                        {products?.map((p: any) => (
                          <option key={p.id} value={p.id}>{p.name}</option>
                        ))}
                      </select>
                    </td>
                    <td className="px-3 py-2">
                      <Input
                        type="number"
                        min="1"
                        value={line.qty}
                        onChange={(e) => updateLine(i, 'qty', e.target.value)}
                        className="w-full"
                      />
                    </td>
                    <td className="px-3 py-2">
                      <Input
                        type="number"
                        min="0"
                        value={line.unit_cost}
                        onChange={(e) => updateLine(i, 'unit_cost', e.target.value)}
                        className="w-full"
                        placeholder="cents"
                      />
                    </td>
                    <td className="px-3 py-2 text-right">
                      {formatCents((parseInt(line.qty) || 0) * (parseInt(line.unit_cost) || 0))}
                    </td>
                    <td className="px-3 py-2">
                      {lines.length > 1 && (
                        <button
                          type="button"
                          onClick={() => removeLine(i)}
                          className="text-muted-foreground hover:text-destructive text-lg leading-none"
                        >
                          ×
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot className="border-t bg-muted/20">
                <tr>
                  <td colSpan={3} className="px-3 py-2 text-right font-medium">Total</td>
                  <td className="px-3 py-2 text-right font-semibold">{formatCents(total)}</td>
                  <td />
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <div className="flex gap-3">
          <Button type="submit" disabled={createMutation.isPending}>
            {createMutation.isPending ? 'Creating…' : 'Create Purchase Order'}
          </Button>
          <Button
            type="button"
            variant="ghost"
            onClick={() => router.push('/purchasing/purchase-orders')}
          >
            Cancel
          </Button>
        </div>
      </form>
    </div>
  );
}
