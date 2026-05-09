export type AccountType = 'asset' | 'liability' | 'equity' | 'income' | 'expense';

export interface Account {
  id: number;
  code: string;
  name: string;
  type: AccountType;
  parent_id: number | null;
  is_active: boolean;
  children?: Account[];
}

export interface JournalLine {
  account_id: number;
  debit: number;  // cents
  credit: number; // cents
  description?: string;
}

export interface JournalEntry {
  id: number;
  date: string;
  reference: string;
  description: string;
  type: 'manual' | 'sale' | 'purchase' | 'payment' | 'pos';
  status: 'draft' | 'posted';
  lines: JournalLine[];
}
