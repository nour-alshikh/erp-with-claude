'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  accountingApi,
  type AccountPayload,
  type JournalEntryFilters,
  type JournalEntryPayload,
} from '../api/accounting';

export function useAccounts() {
  return useQuery({
    queryKey: ['accounts'],
    queryFn: () => accountingApi.getAccounts().then((r) => r.data.data),
  });
}

export function useAccount(id: number) {
  return useQuery({
    queryKey: ['accounts', id],
    queryFn: () => accountingApi.getAccount(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateAccount() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: AccountPayload) =>
      accountingApi.createAccount(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['accounts'] }),
  });
}

export function useUpdateAccount(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<AccountPayload>) =>
      accountingApi.updateAccount(id, data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['accounts'] }),
  });
}

export function useJournalEntries(filters?: JournalEntryFilters) {
  return useQuery({
    queryKey: ['journal-entries', filters],
    queryFn: () => accountingApi.getJournalEntries(filters).then((r) => r.data.data),
  });
}

export function useJournalEntry(id: number) {
  return useQuery({
    queryKey: ['journal-entries', id],
    queryFn: () => accountingApi.getJournalEntry(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateJournalEntry() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: JournalEntryPayload) =>
      accountingApi.createJournalEntry(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['journal-entries'] }),
  });
}

export function useDeleteJournalEntry() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => accountingApi.deleteJournalEntry(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['journal-entries'] }),
  });
}

export function usePostJournalEntry() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => accountingApi.postJournalEntry(id).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['journal-entries'] }),
  });
}
