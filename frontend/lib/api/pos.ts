import client from './client';

export interface OpenSessionPayload {
  opening_float: number;
  warehouse_id: number;
}

export interface CloseSessionPayload {
  actual_cash: number;
}

export interface TransactionLineInput {
  product_id: number;
  qty: number;
  unit_price: number;
}

export interface TransactionPaymentInput {
  method: 'cash' | 'card';
  amount: number;
}

export interface CreateTransactionPayload {
  pos_session_id: number;
  lines: TransactionLineInput[];
  payments: TransactionPaymentInput[];
}

export const posApi = {
  getSessions:   () => client.get('/pos/sessions'),
  getCurrentSession: () => client.get('/pos/sessions/current'),
  openSession:   (data: OpenSessionPayload) => client.post('/pos/sessions/open', data),
  closeSession:  (id: number, data: CloseSessionPayload) =>
    client.post(`/pos/sessions/${id}/close`, data),

  getTransactions:  (sessionId: number) =>
    client.get('/pos/transactions', { params: { session_id: sessionId } }),
  getTransaction:   (id: number) => client.get(`/pos/transactions/${id}`),
  createTransaction: (data: CreateTransactionPayload) =>
    client.post('/pos/transactions', data),
  voidTransaction:  (id: number) => client.post(`/pos/transactions/${id}/void`, {}),
};
