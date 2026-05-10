'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  salesApi,
  type StoreCustomerPayload,
  type StoreQuotationPayload,
  type StorePaymentPayload,
  type ConfirmInvoicePayload,
} from '../api/sales';

// ── Customers ────────────────────────────────────────────────────────────────

export function useCustomers() {
  return useQuery({
    queryKey: ['customers'],
    queryFn: () => salesApi.getCustomers().then((r) => r.data.data),
  });
}

export function useCreateCustomer() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: StoreCustomerPayload) =>
      salesApi.createCustomer(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['customers'] }),
  });
}

export function useUpdateCustomer() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<StoreCustomerPayload> }) =>
      salesApi.updateCustomer(id, data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['customers'] }),
  });
}

export function useDeleteCustomer() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => salesApi.deleteCustomer(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['customers'] }),
  });
}

// ── Quotations ───────────────────────────────────────────────────────────────

export function useQuotations() {
  return useQuery({
    queryKey: ['quotations'],
    queryFn: () => salesApi.getQuotations().then((r) => r.data.data),
  });
}

export function useQuotation(id: number) {
  return useQuery({
    queryKey: ['quotations', id],
    queryFn: () => salesApi.getQuotation(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateQuotation() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: StoreQuotationPayload) =>
      salesApi.createQuotation(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['quotations'] }),
  });
}

export function useConvertQuotation() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => salesApi.convertQuotation(id).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['quotations'] });
      qc.invalidateQueries({ queryKey: ['orders'] });
    },
  });
}

// ── Orders ───────────────────────────────────────────────────────────────────

export function useOrders() {
  return useQuery({
    queryKey: ['orders'],
    queryFn: () => salesApi.getOrders().then((r) => r.data.data),
  });
}

export function useConvertOrder() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => salesApi.convertOrder(id).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['orders'] });
      qc.invalidateQueries({ queryKey: ['invoices'] });
    },
  });
}

// ── Invoices ─────────────────────────────────────────────────────────────────

export function useInvoices() {
  return useQuery({
    queryKey: ['invoices'],
    queryFn: () => salesApi.getInvoices().then((r) => r.data.data),
  });
}

export function useInvoice(id: number) {
  return useQuery({
    queryKey: ['invoices', id],
    queryFn: () => salesApi.getInvoice(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useConfirmInvoice() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: ConfirmInvoicePayload }) =>
      salesApi.confirmInvoice(id, data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['invoices'] });
      qc.invalidateQueries({ queryKey: ['stockLevels'] });
    },
  });
}

// ── Payments ─────────────────────────────────────────────────────────────────

export function useCreatePayment() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: StorePaymentPayload) =>
      salesApi.createPayment(data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['invoices'] });
      qc.invalidateQueries({ queryKey: ['customers'] });
    },
  });
}
