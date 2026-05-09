export interface Customer {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  credit_limit: number; // cents
  balance: number;      // cents
}

export interface QuotationLine {
  product_id: number;
  qty: number;
  unit_price: number; // cents
  discount: number;   // cents
  total: number;      // cents
}

export interface Quotation {
  id: number;
  customer_id: number;
  date: string;
  valid_until: string;
  status: 'draft' | 'sent' | 'accepted' | 'rejected';
  subtotal: number; // cents
  tax: number;      // cents
  total: number;    // cents
  lines: QuotationLine[];
}

export interface Invoice {
  id: number;
  sales_order_id: number | null;
  customer_id: number;
  invoice_number: string;
  date: string;
  due_date: string;
  status: 'unpaid' | 'partial' | 'paid';
  subtotal: number;    // cents
  tax: number;         // cents
  total: number;       // cents
  paid_amount: number; // cents
}
