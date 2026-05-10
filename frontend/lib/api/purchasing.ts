import client from './client';

export interface VendorPayload {
  name: string;
  email?: string | null;
  phone?: string | null;
}

export interface PurchaseOrderLineInput {
  product_id: number;
  qty: number;
  unit_cost: number;
}

export interface PurchaseOrderPayload {
  vendor_id: number;
  date: string;
  notes?: string;
  lines: PurchaseOrderLineInput[];
}

export interface GrnLineInput {
  product_id: number;
  qty_received: number;
  unit_cost: number;
}

export interface GrnPayload {
  purchase_order_id: number;
  warehouse_id: number;
  date: string;
  notes?: string;
  lines: GrnLineInput[];
}

export interface PaymentPayload {
  vendor_bill_id: number;
  amount: number;
  date: string;
  method?: 'cash' | 'bank' | 'card';
  notes?: string;
}

export const purchasingApi = {
  getVendors:   () => client.get('/purchasing/vendors'),
  getVendor:    (id: number) => client.get(`/purchasing/vendors/${id}`),
  createVendor: (data: VendorPayload) => client.post('/purchasing/vendors', data),
  updateVendor: (id: number, data: Partial<VendorPayload>) =>
    client.put(`/purchasing/vendors/${id}`, data),
  deleteVendor: (id: number) => client.delete(`/purchasing/vendors/${id}`),

  getOrders:   () => client.get('/purchasing/purchase-orders'),
  getOrder:    (id: number) => client.get(`/purchasing/purchase-orders/${id}`),
  createOrder: (data: PurchaseOrderPayload) => client.post('/purchasing/purchase-orders', data),
  updateOrder: (id: number, data: Partial<PurchaseOrderPayload>) =>
    client.put(`/purchasing/purchase-orders/${id}`, data),
  deleteOrder: (id: number) => client.delete(`/purchasing/purchase-orders/${id}`),
  sendOrder:   (id: number) => client.post(`/purchasing/purchase-orders/${id}/send`, {}),

  getGrns:    () => client.get('/purchasing/grn'),
  getGrn:     (id: number) => client.get(`/purchasing/grn/${id}`),
  createGrn:  (data: GrnPayload) => client.post('/purchasing/grn', data),
  deleteGrn:  (id: number) => client.delete(`/purchasing/grn/${id}`),
  confirmGrn: (id: number) => client.post(`/purchasing/grn/${id}/confirm`, {}),

  getBills: () => client.get('/purchasing/bills'),
  getBill:  (id: number) => client.get(`/purchasing/bills/${id}`),
  payBill:  (data: PaymentPayload) => client.post('/purchasing/bill-payments', data),
};
