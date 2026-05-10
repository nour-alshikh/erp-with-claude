'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  inventoryApi,
  type ProductPayload,
  type WarehousePayload,
  type StockInPayload,
  type StockOutPayload,
  type StockTransferPayload,
} from '../api/inventory';

export function useProducts() {
  return useQuery({
    queryKey: ['products'],
    queryFn: () => inventoryApi.getProducts().then((r) => r.data.data),
  });
}

export function useProduct(id: number) {
  return useQuery({
    queryKey: ['products', id],
    queryFn: () => inventoryApi.getProduct(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateProduct() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: ProductPayload) =>
      inventoryApi.createProduct(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['products'] }),
  });
}

export function useUpdateProduct(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<ProductPayload>) =>
      inventoryApi.updateProduct(id, data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['products'] });
      qc.invalidateQueries({ queryKey: ['products', id] });
    },
  });
}

export function useDeleteProduct() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => inventoryApi.deleteProduct(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['products'] }),
  });
}

export function useWarehouses() {
  return useQuery({
    queryKey: ['warehouses'],
    queryFn: () => inventoryApi.getWarehouses().then((r) => r.data.data),
  });
}

export function useWarehouse(id: number) {
  return useQuery({
    queryKey: ['warehouses', id],
    queryFn: () => inventoryApi.getWarehouse(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateWarehouse() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: WarehousePayload) =>
      inventoryApi.createWarehouse(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['warehouses'] }),
  });
}

export function useDeleteWarehouse() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => inventoryApi.deleteWarehouse(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['warehouses'] }),
  });
}

export function useStockMovements() {
  return useQuery({
    queryKey: ['stockMovements'],
    queryFn: () => inventoryApi.getMovements().then((r) => r.data.data),
  });
}

export function useStockLevels() {
  return useQuery({
    queryKey: ['stockLevels'],
    queryFn: () => inventoryApi.getLevels().then((r) => r.data.data),
  });
}

export function useLowStock() {
  return useQuery({
    queryKey: ['lowStock'],
    queryFn: () => inventoryApi.getLowStock().then((r) => r.data.data),
  });
}

export function useStockIn() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: StockInPayload) =>
      inventoryApi.stockIn(data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['stockMovements'] });
      qc.invalidateQueries({ queryKey: ['stockLevels'] });
    },
  });
}

export function useStockOut() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: StockOutPayload) =>
      inventoryApi.stockOut(data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['stockMovements'] });
      qc.invalidateQueries({ queryKey: ['stockLevels'] });
      qc.invalidateQueries({ queryKey: ['lowStock'] });
    },
  });
}

export function useTransfer() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: StockTransferPayload) =>
      inventoryApi.transfer(data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['stockMovements'] });
      qc.invalidateQueries({ queryKey: ['stockLevels'] });
    },
  });
}
