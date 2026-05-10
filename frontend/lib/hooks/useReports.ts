'use client';

import { useQuery } from '@tanstack/react-query';
import { reportsApi } from '../api/reports';

export function useTrialBalance(from: string, to: string) {
  return useQuery({
    queryKey: ['trialBalance', from, to],
    queryFn:  () => reportsApi.trialBalance({ from, to }).then((r) => r.data.data),
    enabled:  !!from && !!to,
  });
}

export function useIncomeStatement(from: string, to: string) {
  return useQuery({
    queryKey: ['incomeStatement', from, to],
    queryFn:  () => reportsApi.incomeStatement({ from, to }).then((r) => r.data.data),
    enabled:  !!from && !!to,
  });
}

export function useBalanceSheet(asOf: string) {
  return useQuery({
    queryKey: ['balanceSheet', asOf],
    queryFn:  () => reportsApi.balanceSheet({ as_of: asOf }).then((r) => r.data.data),
    enabled:  !!asOf,
  });
}

export function useArAging() {
  return useQuery({
    queryKey: ['arAging'],
    queryFn:  () => reportsApi.arAging().then((r) => r.data.data),
  });
}

export function useApAging() {
  return useQuery({
    queryKey: ['apAging'],
    queryFn:  () => reportsApi.apAging().then((r) => r.data.data),
  });
}
