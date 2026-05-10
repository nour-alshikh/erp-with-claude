'use client';

import { useState, useMemo } from 'react';
import { useRouter } from 'next/navigation';
import { useCreateQuotation } from '@/lib/hooks/useSales';
import { useCustomers } from '@/lib/hooks/useSales';
import { useProducts } from '@/lib/hooks/useInventory';
import { formatCents } from '@/lib/utils/money';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { toast } from 'sonner';

interface LineItem {
  product_id: number;
  name: string;
  qty: number;
  unit_price: number;
  discount: number;
}

export default function NewQuotationPage() {
  const router = useRouter();
  const { data: customers } = useCustomers();
  const { data: products }  = useProducts();
  const createMutation      = useCreateQuotation();

  const [customerId,  setCustomerId]  = useState('');
  const [date,        setDate]        = useState(new Date().toISOString().slice(0, 10));
  const [validUntil,  setValidUntil]  = useState('');
  const [notes,       setNotes]       = useState('');
  const [lines,       setLines]       = useState<LineItem[]>([]);
  const [productSel,  setProductSel]  = useState('');

  const total = useMemo(
    () => lines.reduce((s, l) => s + l.qty * l.unit_price - l.discount, 0),
    [lines],
  );

  function addLine() {
    if (!productSel) return;
    const product = products?.find((p: any) => p.id === parseInt(productSel));
    if (!product) return;
    const exists = lines.find((l) => l.product_id === product.id);
    if (exists) {
      setLines((prev) =>
        prev.map((l) => l.product_id === product.id ? { ...l, qty: l.qty + 1 } : l),
      );
    } else {
      setLines((prev) => [
        ...prev,
        { product_id: product.id, name: product.name, qty: 1, unit_price: product.selling_price, discount: 0 },
      ]);
    }
    setProductSel('');
  }

  function updateLine(idx: number, field: keyof LineItem, value: number) {
    setLines((prev) => prev.map((l, i) => i === idx ? { ...l, [field]: value } : l));
  }

  function removeLine(idx: number) {
    setLines((prev) => prev.filter((_, i) => i !== idx));
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!customerId) { toast.error('Select a customer'); return; }
    if (lines.length === 0) { toast.error('Add at least one line item'); return; }

    createMutation.mutate(
      {
        customer_id: parseInt(customerId),
        date,
        valid_until: validUntil || undefined,
        notes:       notes || undefined,
        lines: lines.map((l) => ({
          product_id: l.product_id,
          qty:        l.qty,
          unit_price: l.unit_price,
          discount:   l.discount || undefined,
        })),
      },
      {
        onSuccess: () => { toast.success('Quotation created'); router.push('/sales/quotations'); },
        onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Failed'),
      },
    );
  }

  return (
    <div className="max-w-3xl space-y-6">
      <h1 className="text-2xl font-bold">New Quotation</h1>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Header fields */}
        <div className="grid grid-cols-2 gap-4">
          <div className="col-span-2 sm:col-span-1">
            <label className="text-sm font-medium mb-1 block">Customer *</label>
            <select
              className="border rounded-md px-3 py-2 text-sm bg-background w-full"
              value={customerId}
              onChange={(e) => setCustomerId(e.target.value)}
              required
            >
              <option value="">Select customer…</option>
              {customers?.map((c: any) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="text-sm font-medium mb-1 block">Date *</label>
            <Input type="date" value={date} onChange={(e) => setDate(e.target.value)} required />
          </div>
          <div>
            <label className="text-sm font-medium mb-1 block">Valid Until</label>
            <Input type="date" value={validUntil} onChange={(e) => setValidUntil(e.target.value)} />
          </div>
          <div className="col-span-2">
            <label className="text-sm font-medium mb-1 block">Notes</label>
            <Input value={notes} onChange={(e) => setNotes(e.target.value)} placeholder="Optional notes…" />
          </div>
        </div>

        {/* Line items */}
        <div className="space-y-3">
          <h2 className="font-semibold">Line Items</h2>

          {/* Add product */}
          <div className="flex gap-2">
            <select
              className="border rounded-md px-3 py-2 text-sm bg-background flex-1"
              value={productSel}
              onChange={(e) => setProductSel(e.target.value)}
            >
              <option value="">Select product to add…</option>
              {products?.map((p: any) => (
                <option key={p.id} value={p.id}>{p.name} — {formatCents(p.selling_price)}</option>
              ))}
            </select>
            <Button type="button" variant="outline" onClick={addLine} disabled={!productSel}>
              Add
            </Button>
          </div>

          {/* Lines table */}
          {lines.length > 0 && (
            <div className="border rounded-lg overflow-hidden">
              <table className="w-full text-sm">
                <thead className="bg-muted/40">
                  <tr>
                    <th className="text-left px-4 py-2 font-medium">Product</th>
                    <th className="text-right px-4 py-2 font-medium w-24">Qty</th>
                    <th className="text-right px-4 py-2 font-medium w-32">Unit Price (¢)</th>
                    <th className="text-right px-4 py-2 font-medium w-28">Discount (¢)</th>
                    <th className="text-right px-4 py-2 font-medium w-28">Line Total</th>
                    <th className="w-10" />
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {lines.map((l, i) => (
                    <tr key={i}>
                      <td className="px-4 py-2">{l.name}</td>
                      <td className="px-4 py-2">
                        <Input
                          type="number"
                          min="1"
                          value={l.qty}
                          onChange={(e) => updateLine(i, 'qty', parseInt(e.target.value) || 1)}
                          className="w-20 text-right h-8"
                        />
                      </td>
                      <td className="px-4 py-2">
                        <Input
                          type="number"
                          min="0"
                          value={l.unit_price}
                          onChange={(e) => updateLine(i, 'unit_price', parseInt(e.target.value) || 0)}
                          className="w-28 text-right h-8"
                        />
                      </td>
                      <td className="px-4 py-2">
                        <Input
                          type="number"
                          min="0"
                          value={l.discount}
                          onChange={(e) => updateLine(i, 'discount', parseInt(e.target.value) || 0)}
                          className="w-24 text-right h-8"
                        />
                      </td>
                      <td className="px-4 py-2 text-right font-medium">
                        {formatCents(l.qty * l.unit_price - l.discount)}
                      </td>
                      <td className="px-2 py-2 text-center">
                        <button
                          type="button"
                          onClick={() => removeLine(i)}
                          className="text-muted-foreground hover:text-destructive text-lg leading-none"
                        >
                          ×
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
                <tfoot className="bg-muted/20 border-t">
                  <tr>
                    <td colSpan={4} className="px-4 py-2 text-right font-semibold">Total</td>
                    <td className="px-4 py-2 text-right font-bold">{formatCents(total)}</td>
                    <td />
                  </tr>
                </tfoot>
              </table>
            </div>
          )}
        </div>

        <div className="flex gap-2">
          <Button type="submit" disabled={createMutation.isPending}>
            {createMutation.isPending ? 'Creating…' : 'Create Quotation'}
          </Button>
          <Button type="button" variant="ghost" onClick={() => router.back()}>
            Cancel
          </Button>
        </div>
      </form>
    </div>
  );
}
