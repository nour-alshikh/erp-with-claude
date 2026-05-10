'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useJournalEntries, useDeleteJournalEntry, usePostJournalEntry } from '@/lib/hooks/useAccounting';
import { formatCents } from '@/lib/utils/money';
import { buttonVariants } from '@/components/ui/button';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { toast } from 'sonner';

const statusVariant: Record<string, 'default' | 'secondary'> = {
  draft:  'secondary',
  posted: 'default',
};

export default function JournalEntriesPage() {
  const [filters, setFilters] = useState<Record<string, string>>({});
  const { data: entries, isLoading } = useJournalEntries(filters);
  const deleteMutation = useDeleteJournalEntry();
  const postMutation   = usePostJournalEntry();

  function setFilter(key: string, value: string) {
    setFilters((prev) => ({ ...prev, [key]: value || '' }));
  }

  function handleDelete(id: number) {
    if (!confirm('Delete this journal entry?')) return;
    deleteMutation.mutate(id, {
      onSuccess: () => toast.success('Entry deleted'),
      onError:   (e: any) => toast.error(e.response?.data?.message ?? 'Delete failed'),
    });
  }

  function handlePost(id: number) {
    postMutation.mutate(id, {
      onSuccess: () => toast.success('Entry posted'),
      onError:   (e: any) => toast.error(e.response?.data?.message ?? 'Post failed'),
    });
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Journal Entries</h1>
        <Link href="/accounting/journal-entries/new" className={buttonVariants()}>
          New Entry
        </Link>
      </div>

      {/* Filters */}
      <div className="flex gap-3 flex-wrap">
        <Input
          type="date"
          placeholder="From"
          className="w-40"
          onChange={(e) => setFilter('date_from', e.target.value)}
        />
        <Input
          type="date"
          placeholder="To"
          className="w-40"
          onChange={(e) => setFilter('date_to', e.target.value)}
        />
        <Select onValueChange={(v) => setFilter('status', String(v) === 'all' ? '' : String(v))}>
          <SelectTrigger className="w-36">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All statuses</SelectItem>
            <SelectItem value="draft">Draft</SelectItem>
            <SelectItem value="posted">Posted</SelectItem>
          </SelectContent>
        </Select>
        <Select onValueChange={(v) => setFilter('type', String(v) === 'all' ? '' : String(v))}>
          <SelectTrigger className="w-36">
            <SelectValue placeholder="Type" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All types</SelectItem>
            <SelectItem value="manual">Manual</SelectItem>
            <SelectItem value="sale">Sale</SelectItem>
            <SelectItem value="purchase">Purchase</SelectItem>
            <SelectItem value="payment">Payment</SelectItem>
            <SelectItem value="pos">POS</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {isLoading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="border rounded-lg overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Reference</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>Type</TableHead>
                <TableHead className="text-right">Debit</TableHead>
                <TableHead className="text-right">Credit</TableHead>
                <TableHead>Status</TableHead>
                <TableHead />
              </TableRow>
            </TableHeader>
            <TableBody>
              {entries?.length ? (
                entries.map((entry: any) => (
                  <TableRow key={entry.id}>
                    <TableCell>{entry.date}</TableCell>
                    <TableCell className="font-mono text-sm">{entry.reference ?? '—'}</TableCell>
                    <TableCell className="max-w-[200px] truncate">{entry.description ?? '—'}</TableCell>
                    <TableCell className="capitalize">{entry.type}</TableCell>
                    <TableCell className="text-right font-mono">
                      {formatCents(entry.total_debit ?? 0)}
                    </TableCell>
                    <TableCell className="text-right font-mono">
                      {formatCents(entry.total_credit ?? 0)}
                    </TableCell>
                    <TableCell>
                      <Badge variant={statusVariant[entry.status] ?? 'secondary'}>
                        {entry.status}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right">
                      {entry.status === 'draft' && (
                        <div className="flex justify-end gap-2">
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => handlePost(entry.id)}
                            disabled={postMutation.isPending}
                          >
                            Post
                          </Button>
                          <Button
                            size="sm"
                            variant="ghost"
                            className="text-destructive hover:text-destructive"
                            onClick={() => handleDelete(entry.id)}
                            disabled={deleteMutation.isPending}
                          >
                            Delete
                          </Button>
                        </div>
                      )}
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={8} className="text-center text-muted-foreground py-8">
                    No journal entries found.
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
