import client from './client';

export interface AccountPayload {
  code: string;
  name: string;
  type: 'asset' | 'liability' | 'equity' | 'income' | 'expense';
  parent_id?: number | null;
  is_active?: boolean;
}

export interface JournalLinePayload {
  account_id: number;
  debit: number;
  credit: number;
  description?: string;
}

export interface JournalEntryPayload {
  date: string;
  reference?: string;
  description?: string;
  lines: JournalLinePayload[];
}

export interface JournalEntryFilters {
  date_from?: string;
  date_to?: string;
  type?: string;
  status?: string;
}

export const accountingApi = {
  getAccounts:   () => client.get('/accounting/accounts'),
  getAccount:    (id: number) => client.get(`/accounting/accounts/${id}`),
  createAccount: (data: AccountPayload) => client.post('/accounting/accounts', data),
  updateAccount: (id: number, data: Partial<AccountPayload>) =>
    client.put(`/accounting/accounts/${id}`, data),

  getJournalEntries: (filters?: JournalEntryFilters) =>
    client.get('/accounting/journal-entries', { params: filters }),
  getJournalEntry: (id: number) => client.get(`/accounting/journal-entries/${id}`),
  createJournalEntry: (data: JournalEntryPayload) =>
    client.post('/accounting/journal-entries', data),
  updateJournalEntry: (id: number, data: JournalEntryPayload) =>
    client.put(`/accounting/journal-entries/${id}`, data),
  deleteJournalEntry: (id: number) => client.delete(`/accounting/journal-entries/${id}`),
  postJournalEntry:   (id: number) => client.post(`/accounting/journal-entries/${id}/post`, {}),
};
