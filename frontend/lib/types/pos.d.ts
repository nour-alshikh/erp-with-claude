export interface PosSession {
  id: number;
  opened_by: number;
  closed_by: number | null;
  opened_at: string;
  closed_at: string | null;
  opening_float: number;  // cents
  expected_cash: number;  // cents
  actual_cash: number | null; // cents
  status: 'open' | 'closed';
}

export interface PosTransactionLine {
  product_id: number;
  qty: number;
  unit_price: number; // cents
  total: number;      // cents
}

export interface PosPayment {
  method: 'cash' | 'card';
  amount: number; // cents
}

export interface PosTransaction {
  id: number;
  pos_session_id: number;
  transaction_number: string;
  date: string;
  subtotal: number; // cents
  tax: number;      // cents
  total: number;    // cents
  status: 'completed' | 'voided';
  lines: PosTransactionLine[];
  payments: PosPayment[];
}
