'use client';

import { useState } from 'react';
import { useCustomers, useCreateCustomer, useUpdateCustomer, useDeleteCustomer } from '@/lib/hooks/useSales';
import { formatCents } from '@/lib/utils/money';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { toast } from 'sonner';

interface CustomerForm {
  name: string;
  email: string;
  phone: string;
  credit_limit: string;
}

const emptyForm: CustomerForm = { name: '', email: '', phone: '', credit_limit: '' };

export default function CustomersPage() {
  const { data: customers, isLoading } = useCustomers();
  const createMutation = useCreateCustomer();
  const updateMutation = useUpdateCustomer();
  const deleteMutation = useDeleteCustomer();

  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing]   = useState<any>(null);
  const [form, setForm]         = useState<CustomerForm>(emptyForm);

  function openCreate() {
    setEditing(null);
    setForm(emptyForm);
    setShowForm(true);
  }

  function openEdit(c: any) {
    setEditing(c);
    setForm({
      name:         c.name,
      email:        c.email ?? '',
      phone:        c.phone ?? '',
      credit_limit: c.credit_limit ? String(c.credit_limit) : '',
    });
    setShowForm(true);
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const payload = {
      name:         form.name,
      email:        form.email || undefined,
      phone:        form.phone || undefined,
      credit_limit: form.credit_limit ? parseInt(form.credit_limit) : undefined,
    };
    if (editing) {
      updateMutation.mutate(
        { id: editing.id, data: payload },
        {
          onSuccess: () => { toast.success('Customer updated'); setShowForm(false); },
          onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Failed'),
        },
      );
    } else {
      createMutation.mutate(payload, {
        onSuccess: () => { toast.success('Customer created'); setShowForm(false); },
        onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Failed'),
      });
    }
  }

  function handleDelete(id: number) {
    if (!confirm('Delete this customer?')) return;
    deleteMutation.mutate(id, {
      onSuccess: () => toast.success('Customer deleted'),
      onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Failed'),
    });
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Customers</h1>
        <Button onClick={openCreate}>+ New Customer</Button>
      </div>

      {showForm && (
        <div className="border rounded-xl p-6 bg-muted/20 space-y-4">
          <h2 className="font-semibold">{editing ? 'Edit Customer' : 'New Customer'}</h2>
          <form onSubmit={handleSubmit} className="grid grid-cols-2 gap-4">
            <div className="col-span-2 sm:col-span-1">
              <label className="text-sm font-medium mb-1 block">Name *</label>
              <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
            </div>
            <div>
              <label className="text-sm font-medium mb-1 block">Email</label>
              <Input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
            </div>
            <div>
              <label className="text-sm font-medium mb-1 block">Phone</label>
              <Input value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
            </div>
            <div>
              <label className="text-sm font-medium mb-1 block">Credit Limit (¢)</label>
              <Input
                type="number"
                min="0"
                value={form.credit_limit}
                onChange={(e) => setForm({ ...form, credit_limit: e.target.value })}
                placeholder="e.g. 500000"
              />
            </div>
            <div className="col-span-2 flex gap-2">
              <Button type="submit" disabled={createMutation.isPending || updateMutation.isPending}>
                {editing ? 'Update' : 'Create'}
              </Button>
              <Button type="button" variant="ghost" onClick={() => setShowForm(false)}>Cancel</Button>
            </div>
          </form>
        </div>
      )}

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
                <TableHead className="text-right">Credit Limit</TableHead>
                <TableHead className="text-right">Balance (AR)</TableHead>
                <TableHead />
              </TableRow>
            </TableHeader>
            <TableBody>
              {customers?.length ? customers.map((c: any) => (
                <TableRow key={c.id}>
                  <TableCell className="font-medium">{c.name}</TableCell>
                  <TableCell className="text-sm text-muted-foreground">{c.email ?? '—'}</TableCell>
                  <TableCell className="text-sm">{c.phone ?? '—'}</TableCell>
                  <TableCell className="text-right">{c.credit_limit ? formatCents(c.credit_limit) : '—'}</TableCell>
                  <TableCell className="text-right">
                    <span className={c.balance > 0 ? 'text-amber-600 font-medium' : ''}>
                      {formatCents(c.balance)}
                    </span>
                  </TableCell>
                  <TableCell>
                    <div className="flex justify-end gap-2">
                      <Button size="sm" variant="outline" onClick={() => openEdit(c)}>Edit</Button>
                      <Button size="sm" variant="ghost" onClick={() => handleDelete(c.id)}
                        className="text-destructive hover:text-destructive">
                        Delete
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              )) : (
                <TableRow>
                  <TableCell colSpan={6} className="text-center text-muted-foreground py-8">
                    No customers yet.
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
