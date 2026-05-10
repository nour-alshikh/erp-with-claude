'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  purchasingApi,
  type VendorPayload,
  type PurchaseOrderPayload,
  type GrnPayload,
  type PaymentPayload,
} from '../api/purchasing';

export function useVendors() {
  return useQuery({
    queryKey: ['vendors'],
    queryFn: () => purchasingApi.getVendors().then((r) => r.data.data),
  });
}

export function useVendor(id: number) {
  return useQuery({
    queryKey: ['vendors', id],
    queryFn: () => purchasingApi.getVendor(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateVendor() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: VendorPayload) =>
      purchasingApi.createVendor(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['vendors'] }),
  });
}

export function useDeleteVendor() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => purchasingApi.deleteVendor(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['vendors'] }),
  });
}

export function usePurchaseOrders() {
  return useQuery({
    queryKey: ['purchaseOrders'],
    queryFn: () => purchasingApi.getOrders().then((r) => r.data.data),
  });
}

export function usePurchaseOrder(id: number) {
  return useQuery({
    queryKey: ['purchaseOrders', id],
    queryFn: () => purchasingApi.getOrder(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreatePurchaseOrder() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: PurchaseOrderPayload) =>
      purchasingApi.createOrder(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['purchaseOrders'] }),
  });
}

export function useDeletePurchaseOrder() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => purchasingApi.deleteOrder(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['purchaseOrders'] }),
  });
}

export function useSendPurchaseOrder() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => purchasingApi.sendOrder(id).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['purchaseOrders'] }),
  });
}

export function useGrns() {
  return useQuery({
    queryKey: ['grns'],
    queryFn: () => purchasingApi.getGrns().then((r) => r.data.data),
  });
}

export function useGrn(id: number) {
  return useQuery({
    queryKey: ['grns', id],
    queryFn: () => purchasingApi.getGrn(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateGrn() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: GrnPayload) =>
      purchasingApi.createGrn(data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['grns'] });
      qc.invalidateQueries({ queryKey: ['vendorBills'] });
    },
  });
}

export function useConfirmGrn() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => purchasingApi.confirmGrn(id).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['grns'] });
      qc.invalidateQueries({ queryKey: ['vendorBills'] });
      qc.invalidateQueries({ queryKey: ['stockLevels'] });
      qc.invalidateQueries({ queryKey: ['vendors'] });
    },
  });
}

export function useVendorBills() {
  return useQuery({
    queryKey: ['vendorBills'],
    queryFn: () => purchasingApi.getBills().then((r) => r.data.data),
  });
}

export function useVendorBill(id: number) {
  return useQuery({
    queryKey: ['vendorBills', id],
    queryFn: () => purchasingApi.getBill(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function usePayBill() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: PaymentPayload) =>
      purchasingApi.payBill(data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['vendorBills'] });
      qc.invalidateQueries({ queryKey: ['vendors'] });
    },
  });
}
