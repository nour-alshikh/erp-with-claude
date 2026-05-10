'use client';

import { useState, useMemo, useRef, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { useCurrentSession, useCreateTransaction } from '@/lib/hooks/usePOS';
import { useProducts } from '@/lib/hooks/useInventory';
import { formatCents } from '@/lib/utils/money';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { toast } from 'sonner';

interface CartLine {
  product_id: number;
  name: string;
  qty: number;
  unit_price: number;
}

export default function PosTerminalPage() {
  const router   = useRouter();
  const { data: session, isLoading: sessionLoading } = useCurrentSession();
  const { data: products, isLoading: productsLoading } = useProducts();
  const createMutation = useCreateTransaction();

  const [search, setSearch]     = useState('');
  const [cart, setCart]         = useState<CartLine[]>([]);
  const [cashInput, setCashInput] = useState('');
  const [cardInput, setCardInput] = useState('');
  const searchRef               = useRef<HTMLInputElement>(null);

  // Filter products by search
  const filtered = useMemo(() => {
    if (!products) return [];
    const q = search.toLowerCase();
    return q
      ? products.filter(
          (p: any) =>
            p.name.toLowerCase().includes(q) || (p.sku ?? '').toLowerCase().includes(q),
        )
      : products;
  }, [products, search]);

  const cartTotal   = useMemo(() => cart.reduce((s, l) => s + l.qty * l.unit_price, 0), [cart]);
  const cashPaid    = parseInt(cashInput) || 0;
  const cardPaid    = parseInt(cardInput) || 0;
  const totalPaid   = cashPaid + cardPaid;
  const changeDue   = totalPaid - cartTotal;
  const canComplete = cart.length > 0 && totalPaid >= cartTotal;

  function addToCart(product: any) {
    setCart((prev) => {
      const existing = prev.find((l) => l.product_id === product.id);
      if (existing) {
        return prev.map((l) =>
          l.product_id === product.id ? { ...l, qty: l.qty + 1 } : l,
        );
      }
      return [
        ...prev,
        {
          product_id: product.id,
          name:       product.name,
          qty:        1,
          unit_price: product.selling_price,
        },
      ];
    });
  }

  function updateQty(productId: number, delta: number) {
    setCart((prev) =>
      prev
        .map((l) => (l.product_id === productId ? { ...l, qty: l.qty + delta } : l))
        .filter((l) => l.qty > 0),
    );
  }

  function removeFromCart(productId: number) {
    setCart((prev) => prev.filter((l) => l.product_id !== productId));
  }

  function clearCart() {
    setCart([]);
    setCashInput('');
    setCardInput('');
    searchRef.current?.focus();
  }

  function handleComplete() {
    if (!session || !canComplete) return;

    const payments: { method: 'cash' | 'card'; amount: number }[] = [];
    if (cashPaid > 0) payments.push({ method: 'cash', amount: cashPaid });
    if (cardPaid > 0) payments.push({ method: 'card', amount: cardPaid });

    createMutation.mutate(
      {
        pos_session_id: session.id,
        lines: cart.map((l) => ({
          product_id: l.product_id,
          qty:        l.qty,
          unit_price: l.unit_price,
        })),
        payments,
      },
      {
        onSuccess: (tx: any) => {
          toast.success(`Sale ${tx.transaction_number} completed`);
          clearCart();
        },
        onError: (e: any) =>
          toast.error(e?.response?.data?.message ?? 'Transaction failed'),
      },
    );
  }

  // Keyboard shortcut: / to focus search
  const handleKeyDown = useCallback(
    (e: React.KeyboardEvent) => {
      if (e.key === '/' && document.activeElement !== searchRef.current) {
        e.preventDefault();
        searchRef.current?.focus();
      }
      if (e.key === 'Enter' && canComplete && !createMutation.isPending) {
        handleComplete();
      }
    },
    [canComplete, createMutation.isPending],
  );

  if (sessionLoading) {
    return <p className="text-muted-foreground">Loading…</p>;
  }

  if (!session) {
    return (
      <div className="flex flex-col items-center justify-center h-64 gap-4">
        <p className="text-lg text-muted-foreground">No active POS session.</p>
        <Button onClick={() => router.push('/pos/sessions')}>Open a Session</Button>
      </div>
    );
  }

  return (
    // eslint-disable-next-line jsx-a11y/no-static-element-interactions
    <div className="flex gap-0 h-[calc(100vh-4rem)] -m-6 outline-none" onKeyDown={handleKeyDown} tabIndex={-1}>
      {/* Left panel — product browser */}
      <div className="flex-1 flex flex-col border-r bg-muted/10">
        {/* Search */}
        <div className="p-4 border-b">
          <Input
            ref={searchRef}
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search products… (press / to focus)"
            autoFocus
          />
        </div>

        {/* Product grid */}
        <div className="flex-1 overflow-y-auto p-4">
          {productsLoading ? (
            <p className="text-muted-foreground text-sm">Loading products…</p>
          ) : filtered.length === 0 ? (
            <p className="text-muted-foreground text-sm">No products match.</p>
          ) : (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
              {filtered.map((p: any) => (
                <button
                  key={p.id}
                  onClick={() => addToCart(p)}
                  className="border rounded-xl p-4 text-left bg-background hover:bg-accent hover:border-primary transition-colors active:scale-95"
                >
                  <p className="font-semibold text-sm leading-tight line-clamp-2">{p.name}</p>
                  {p.sku && (
                    <p className="text-xs text-muted-foreground mt-1">{p.sku}</p>
                  )}
                  <p className="text-primary font-bold mt-2">{formatCents(p.selling_price)}</p>
                </button>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Right panel — cart + payment */}
      <div className="w-96 flex flex-col bg-background">
        {/* Session info strip */}
        <div className="px-4 py-2 border-b bg-muted/30 text-xs text-muted-foreground flex justify-between">
          <span>{session.warehouse?.name}</span>
          <Badge variant="default" className="text-xs">Session #{session.id}</Badge>
        </div>

        {/* Cart items */}
        <div className="flex-1 overflow-y-auto">
          {cart.length === 0 ? (
            <div className="flex items-center justify-center h-full text-muted-foreground text-sm">
              Cart is empty — tap a product to add
            </div>
          ) : (
            <div className="divide-y">
              {cart.map((line) => (
                <div key={line.product_id} className="flex items-center gap-2 px-4 py-3">
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium truncate">{line.name}</p>
                    <p className="text-xs text-muted-foreground">{formatCents(line.unit_price)} each</p>
                  </div>
                  <div className="flex items-center gap-1">
                    <button
                      onClick={() => updateQty(line.product_id, -1)}
                      className="w-7 h-7 rounded-full border flex items-center justify-center text-lg hover:bg-muted"
                    >
                      −
                    </button>
                    <span className="w-6 text-center text-sm font-semibold">{line.qty}</span>
                    <button
                      onClick={() => updateQty(line.product_id, 1)}
                      className="w-7 h-7 rounded-full border flex items-center justify-center text-lg hover:bg-muted"
                    >
                      +
                    </button>
                  </div>
                  <span className="w-20 text-right text-sm font-semibold">
                    {formatCents(line.qty * line.unit_price)}
                  </span>
                  <button
                    onClick={() => removeFromCart(line.product_id)}
                    className="text-muted-foreground hover:text-destructive text-lg ml-1"
                  >
                    ×
                  </button>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Totals + payment */}
        <div className="border-t p-4 space-y-4">
          {/* Total */}
          <div className="flex justify-between text-xl font-bold">
            <span>Total</span>
            <span>{formatCents(cartTotal)}</span>
          </div>

          {/* Payment inputs */}
          <div className="space-y-2">
            <div className="flex items-center gap-2">
              <label className="text-sm w-12">Cash</label>
              <Input
                type="number"
                min="0"
                value={cashInput}
                onChange={(e) => setCashInput(e.target.value)}
                placeholder="0"
                className="flex-1 text-right"
              />
            </div>
            <div className="flex items-center gap-2">
              <label className="text-sm w-12">Card</label>
              <Input
                type="number"
                min="0"
                value={cardInput}
                onChange={(e) => setCardInput(e.target.value)}
                placeholder="0"
                className="flex-1 text-right"
              />
            </div>
          </div>

          {/* Change due */}
          {totalPaid > 0 && (
            <div className="flex justify-between text-sm">
              <span className="text-muted-foreground">Change Due</span>
              <span className={changeDue >= 0 ? 'text-green-600 font-semibold' : 'text-destructive font-semibold'}>
                {changeDue >= 0 ? formatCents(changeDue) : `Short ${formatCents(Math.abs(changeDue))}`}
              </span>
            </div>
          )}

          {/* Action buttons */}
          <div className="space-y-2">
            <Button
              className="w-full h-14 text-lg"
              disabled={!canComplete || createMutation.isPending}
              onClick={handleComplete}
            >
              {createMutation.isPending ? 'Processing…' : 'Complete Sale ↵'}
            </Button>
            <Button
              variant="ghost"
              className="w-full"
              onClick={clearCart}
              disabled={cart.length === 0}
            >
              Clear Cart
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}
