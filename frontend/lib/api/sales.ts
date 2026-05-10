import client from './client';

export interface StoreCustomerPayload {
  name: string;
  email?: string;
  phone?: string;
  credit_limit?: number;
}

export interface QuotationLineInput {
  product_id: number;
  qty: number;
  unit_price: number;
  discount?: number;
}

export interface StoreQuotationPayload {
  customer_id: number;
  date: string;
  valid_until?: string;
  notes?: string;
  lines: QuotationLineInput[];
}

export interface StorePaymentPayload {
  invoice_id: number;
  amount: number;
  date?: string;
  method?: string;
  notes?: string;
}

export interface ConfirmInvoicePayload {
  warehouse_id: number;
}

export const salesApi = {
  // Customers
  getCustomers:   () => client.get('/sales/customers'),
  getCustomer:    (id: number) => client.get(`/sales/customers/${id}`),
  createCustomer: (data: StoreCustomerPayload) => client.post('/sales/customers', data),
  updateCustomer: (id: number, data: Partial<StoreCustomerPayload>) =>
    client.put(`/sales/customers/${id}`, data),
  deleteCustomer: (id: number) => client.delete(`/sales/customers/${id}`),

  // Quotations
  getQuotations:    () => client.get('/sales/quotations'),
  getQuotation:     (id: number) => client.get(`/sales/quotations/${id}`),
  createQuotation:  (data: StoreQuotationPayload) => client.post('/sales/quotations', data),
  updateQuotation:  (id: number, data: StoreQuotationPayload) =>
    client.put(`/sales/quotations/${id}`, data),
  convertQuotation: (id: number) => client.post(`/sales/quotations/${id}/convert`, {}),

  // Orders
  getOrders:    () => client.get('/sales/orders'),
  getOrder:     (id: number) => client.get(`/sales/orders/${id}`),
  convertOrder: (id: number) => client.post(`/sales/orders/${id}/invoice`, {}),

  // Invoices
  getInvoices:    () => client.get('/sales/invoices'),
  getInvoice:     (id: number) => client.get(`/sales/invoices/${id}`),
  confirmInvoice: (id: number, data: ConfirmInvoicePayload) =>
    client.post(`/sales/invoices/${id}/confirm`, data),

  // Payments
  createPayment: (data: StorePaymentPayload) => client.post('/sales/payments', data),
};
