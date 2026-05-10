'use client';

import { useState, useMemo } from 'react';
import { useRouter } from 'next/navigation';
import { useAccounts } from '@/lib/hooks/useAccounting';
import { useCreateJournalEntry } from '@/lib/hooks/useAccounting';
import { formatCents, toCents } from '@/lib/utils/money';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

interface LineItem {
  account_id: number | null;
  description: string;
  debit: string;
  credit: string;
}

const emptyLine = (): LineItem => ({ account_id: null, description: '', debit: '', credit: '' });

export default function NewJournalEntryPage() {
  const router = useRouter();
  const { data: accounts } = useAccounts();
  const createMutation = useCreateJournalEntry();

  const [date, setDate]              = useState(new Date().toISOString().slice(0, 10));
  const [reference, setReference]    = useState('');
  const [description, setDescription] = useState('');
  const [lines, setLines]            = useState<LineItem[]>([emptyLine(), emptyLine()]);

  const totalDebit  = useMemo(() => lines.reduce((s, l) => s + toCents(l.debit  || '0'), 0), [lines]);
  const totalCredit = useMemo(() => lines.reduce((s, l) => s + toCents(l.credit || '0'), 0), [lines]);
  const balanced    = totalDebit > 0 && totalDebit === totalCredit;

  function updateLine(index: number, field: keyof LineItem, value: string | number | null) {
    setLines((prev) =>
      prev.map((l, i) => (i === index ? { ...l, [field]: value } : l))
    );
  }

  function addLine() {
    setLines((prev) => [...prev, emptyLine()]);
  }

  function removeLine(index: number) {
    if (lines.length <= 2) return;
    setLines((prev) => prev.filter((_, i) => i !== index));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!balanced) {
      toast.error('Entry is unbalanced — debits must equal credits.');
      return;
    }

    const payload = {
      date,
      reference:   reference || undefined,
      description: description || undefined,
      lines: lines
        .filter((l) => l.account_id)
        .map((l) => ({
          account_id:  l.account_id as number,
          debit:       toCents(l.debit  || '0'),
          credit:      toCents(l.credit || '0'),
          description: l.description || undefined,
        })),
    };

    createMutation.mutate(payload, {
      onSuccess: () => {
        toast.success('Journal entry created');
        router.push('/accounting/journal-entries');
      },
      onError: (e: any) =>
        toast.error(e.response?.data?.message ?? e.response?.data?.errors?.lines ?? 'Failed to create entry'),
    });
  }

  return (
    <div className="max-w-4xl space-y-6">
      <h1 className="text-2xl font-bold">New Journal Entry</h1>

      <form onSubmit={handleSubmit} className="space-y-6">
        <Card>
          <CardHeader><CardTitle>Header</CardTitle></CardHeader>
          <CardContent className="grid grid-cols-3 gap-4">
            <div className="space-y-1">
              <label className="text-sm font-medium">Date *</label>
              <Input type="date" value={date} onChange={(e) => setDate(e.target.value)} required />
            </div>
            <div className="space-y-1">
              <label className="text-sm font-medium">Reference</label>
              <Input
                placeholder="e.g. INV-001"
                value={reference}
                onChange={(e) => setReference(e.target.value)}
              />
            </div>
            <div className="space-y-1 col-span-1">
              <label className="text-sm font-medium">Description</label>
              <Input
                placeholder="Optional note"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
              />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Lines</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {/* Header row */}
            <div className="grid grid-cols-[1fr_1fr_100px_100px_32px] gap-2 text-xs font-medium text-muted-foreground px-1">
              <span>Account</span>
              <span>Description</span>
              <span className="text-right">Debit</span>
              <span className="text-right">Credit</span>
              <span />
            </div>

            {lines.map((line, idx) => (
              <div key={idx} className="grid grid-cols-[1fr_1fr_100px_100px_32px] gap-2 items-center">
                <Select
                  value={line.account_id ? String(line.account_id) : ''}
                  onValueChange={(v) => updateLine(idx, 'account_id', Number(v))}
                >
                  <SelectTrigger className="h-8 text-sm">
                    <SelectValue placeholder="Select account" />
                  </SelectTrigger>
                  <SelectContent>
                    {accounts?.map((a: any) => (
                      <SelectItem key={a.id} value={String(a.id)}>
                        {a.code} — {a.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>

                <Input
                  className="h-8 text-sm"
                  placeholder="Description"
                  value={line.description}
                  onChange={(e) => updateLine(idx, 'description', e.target.value)}
                />

                <Input
                  className="h-8 text-sm text-right"
                  type="number"
                  min="0"
                  step="0.01"
                  placeholder="0.00"
                  value={line.debit}
                  onChange={(e) => {
                    updateLine(idx, 'debit', e.target.value);
                    if (e.target.value) updateLine(idx, 'credit', '');
                  }}
                />

                <Input
                  className="h-8 text-sm text-right"
                  type="number"
                  min="0"
                  step="0.01"
                  placeholder="0.00"
                  value={line.credit}
                  onChange={(e) => {
                    updateLine(idx, 'credit', e.target.value);
                    if (e.target.value) updateLine(idx, 'debit', '');
                  }}
                />

                <button
                  type="button"
                  onClick={() => removeLine(idx)}
                  disabled={lines.length <= 2}
                  className="text-muted-foreground hover:text-destructive disabled:opacity-30 text-lg leading-none"
                >
                  ×
                </button>
              </div>
            ))}

            <Button type="button" variant="ghost" size="sm" onClick={addLine}>
              + Add Line
            </Button>

            {/* Totals */}
            <div className="grid grid-cols-[1fr_1fr_100px_100px_32px] gap-2 border-t pt-2 mt-2">
              <span className="col-span-2 text-sm font-medium text-right pr-2">Totals</span>
              <span
                className={cn(
                  'text-right font-mono text-sm font-semibold',
                  balanced ? 'text-green-600' : 'text-destructive'
                )}
              >
                {formatCents(totalDebit)}
              </span>
              <span
                className={cn(
                  'text-right font-mono text-sm font-semibold',
                  balanced ? 'text-green-600' : 'text-destructive'
                )}
              >
                {formatCents(totalCredit)}
              </span>
              <span />
            </div>

            {!balanced && totalDebit + totalCredit > 0 && (
              <p className="text-sm text-destructive">
                Difference: {formatCents(Math.abs(totalDebit - totalCredit))} — entry must balance.
              </p>
            )}
          </CardContent>
        </Card>

        <div className="flex gap-3">
          <Button type="submit" disabled={!balanced || createMutation.isPending}>
            {createMutation.isPending ? 'Saving…' : 'Save Draft'}
          </Button>
          <Button
            type="button"
            variant="outline"
            onClick={() => router.push('/accounting/journal-entries')}
          >
            Cancel
          </Button>
        </div>
      </form>
    </div>
  );
}
