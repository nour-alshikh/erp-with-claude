'use client';

import { useState } from 'react';
import Link from 'next/link';
import { usePosSessions, useCurrentSession, useOpenSession, useCloseSession } from '@/lib/hooks/usePOS';
import { useWarehouses } from '@/lib/hooks/useInventory';
import { formatCents } from '@/lib/utils/money';
import { Button, buttonVariants } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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

export default function PosSessionsPage() {
  const { data: sessions, isLoading } = usePosSessions();
  const { data: currentSession }      = useCurrentSession();
  const { data: warehouses }          = useWarehouses();
  const openMutation  = useOpenSession();
  const closeMutation = useCloseSession();

  const [openFloat, setOpenFloat]       = useState('');
  const [warehouseId, setWarehouseId]   = useState('');
  const [actualCash, setActualCash]     = useState('');
  const [showCloseForm, setShowCloseForm] = useState(false);

  function handleOpen(e: React.FormEvent) {
    e.preventDefault();
    openMutation.mutate(
      { opening_float: parseInt(openFloat) || 0, warehouse_id: Number(warehouseId) },
      {
        onSuccess: () => { toast.success('Session opened'); setOpenFloat(''); setWarehouseId(''); },
        onError:   (e: any) => toast.error(e?.response?.data?.message ?? 'Failed to open session'),
      },
    );
  }

  function handleClose(e: React.FormEvent) {
    e.preventDefault();
    if (!currentSession) return;
    closeMutation.mutate(
      { id: currentSession.id, data: { actual_cash: parseInt(actualCash) || 0 } },
      {
        onSuccess: () => { toast.success('Session closed'); setActualCash(''); setShowCloseForm(false); },
        onError:   (e: any) => toast.error(e?.response?.data?.message ?? 'Failed to close session'),
      },
    );
  }

  return (
    <div className="space-y-8">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">POS Sessions</h1>
        {currentSession && (
          <Link href="/pos/terminal" className={buttonVariants({ size: 'sm' })}>
            Open Terminal
          </Link>
        )}
      </div>

      {/* Current session banner */}
      {currentSession ? (
        <div className="border rounded-xl p-6 bg-muted/30 space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="font-semibold text-lg">Active Session</p>
              <p className="text-sm text-muted-foreground">
                Warehouse: {currentSession.warehouse?.name} · Opened {currentSession.opened_at}
              </p>
            </div>
            <Badge variant="default">Open</Badge>
          </div>
          <div className="grid grid-cols-3 gap-4 text-sm">
            <div>
              <span className="text-muted-foreground block">Opening Float</span>
              <span className="font-semibold">{formatCents(currentSession.opening_float)}</span>
            </div>
            <div>
              <span className="text-muted-foreground block">Expected Cash</span>
              <span className="font-semibold">{formatCents(currentSession.expected_cash)}</span>
            </div>
          </div>

          {!showCloseForm ? (
            <Button variant="destructive" size="sm" onClick={() => setShowCloseForm(true)}>
              Close Session
            </Button>
          ) : (
            <form onSubmit={handleClose} className="flex items-end gap-3 mt-2">
              <div>
                <label className="text-sm font-medium mb-1 block">Actual Cash Count (¢)</label>
                <Input
                  type="number"
                  min="0"
                  value={actualCash}
                  onChange={(e) => setActualCash(e.target.value)}
                  placeholder="Enter counted cash…"
                  className="w-52"
                  required
                />
              </div>
              <Button type="submit" variant="destructive" disabled={closeMutation.isPending}>
                {closeMutation.isPending ? 'Closing…' : 'Confirm Close'}
              </Button>
              <Button type="button" variant="ghost" onClick={() => setShowCloseForm(false)}>
                Cancel
              </Button>
            </form>
          )}
        </div>
      ) : (
        <div className="border rounded-xl p-6 space-y-4">
          <p className="font-semibold">Open New Session</p>
          <form onSubmit={handleOpen} className="flex items-end gap-3">
            <div>
              <label className="text-sm font-medium mb-1 block">Warehouse</label>
              <select
                className="border rounded-md px-3 py-2 text-sm bg-background"
                value={warehouseId}
                onChange={(e) => setWarehouseId(e.target.value)}
                required
              >
                <option value="">Select warehouse…</option>
                {warehouses?.map((w: any) => (
                  <option key={w.id} value={w.id}>{w.name}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="text-sm font-medium mb-1 block">Opening Float (¢)</label>
              <Input
                type="number"
                min="0"
                value={openFloat}
                onChange={(e) => setOpenFloat(e.target.value)}
                placeholder="e.g. 20000"
                className="w-40"
                required
              />
            </div>
            <Button type="submit" disabled={openMutation.isPending}>
              {openMutation.isPending ? 'Opening…' : 'Open Session'}
            </Button>
          </form>
        </div>
      )}

      {/* Sessions history */}
      <div>
        <h2 className="text-lg font-semibold mb-3">Session History</h2>
        {isLoading ? (
          <p className="text-muted-foreground">Loading…</p>
        ) : (
          <div className="border rounded-lg overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>#</TableHead>
                  <TableHead>Warehouse</TableHead>
                  <TableHead>Opened</TableHead>
                  <TableHead>Closed</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Expected Cash</TableHead>
                  <TableHead className="text-right">Actual Cash</TableHead>
                  <TableHead className="text-right">Variance</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {sessions?.data?.length || sessions?.length ? (
                  (sessions?.data ?? sessions).map((s: any) => (
                    <TableRow key={s.id}>
                      <TableCell>{s.id}</TableCell>
                      <TableCell>{s.warehouse?.name ?? '—'}</TableCell>
                      <TableCell className="text-sm">{s.opened_at}</TableCell>
                      <TableCell className="text-sm">{s.closed_at ?? '—'}</TableCell>
                      <TableCell>
                        <Badge variant={s.status === 'open' ? 'default' : 'secondary'}>
                          {s.status}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right">{formatCents(s.expected_cash)}</TableCell>
                      <TableCell className="text-right">
                        {s.actual_cash != null ? formatCents(s.actual_cash) : '—'}
                      </TableCell>
                      <TableCell className={`text-right font-medium ${s.variance < 0 ? 'text-destructive' : ''}`}>
                        {s.variance != null
                          ? (s.variance >= 0 ? '+' : '') + formatCents(Math.abs(s.variance))
                          : '—'}
                      </TableCell>
                    </TableRow>
                  ))
                ) : (
                  <TableRow>
                    <TableCell colSpan={8} className="text-center text-muted-foreground py-8">
                      No sessions found.
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
