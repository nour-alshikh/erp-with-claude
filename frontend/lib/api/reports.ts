import client from './client';

export const reportsApi = {
  trialBalance:    (params?: { from?: string; to?: string }) =>
    client.get('/reports/trial-balance', { params }),
  incomeStatement: (params?: { from?: string; to?: string }) =>
    client.get('/reports/income-statement', { params }),
  balanceSheet:    (params?: { as_of?: string }) =>
    client.get('/reports/balance-sheet', { params }),
  arAging:         () => client.get('/reports/ar-aging'),
  apAging:         () => client.get('/reports/ap-aging'),
};
