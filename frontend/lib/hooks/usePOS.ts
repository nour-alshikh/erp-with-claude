'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  posApi,
  type OpenSessionPayload,
  type CloseSessionPayload,
  type CreateTransactionPayload,
} from '../api/pos';

export function usePosSessions() {
  return useQuery({
    queryKey: ['posSessions'],
    queryFn: () => posApi.getSessions().then((r) => r.data.data),
  });
}

export function useCurrentSession() {
  return useQuery({
    queryKey: ['currentPosSession'],
    queryFn: () => posApi.getCurrentSession().then((r) => r.data.data),
    retry: false,
  });
}

export function useOpenSession() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: OpenSessionPayload) =>
      posApi.openSession(data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['posSessions'] });
      qc.invalidateQueries({ queryKey: ['currentPosSession'] });
    },
  });
}

export function useCloseSession() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: CloseSessionPayload }) =>
      posApi.closeSession(id, data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['posSessions'] });
      qc.invalidateQueries({ queryKey: ['currentPosSession'] });
    },
  });
}

export function usePosTransactions(sessionId: number) {
  return useQuery({
    queryKey: ['posTransactions', sessionId],
    queryFn: () => posApi.getTransactions(sessionId).then((r) => r.data.data),
    enabled: !!sessionId,
  });
}

export function useCreateTransaction() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateTransactionPayload) =>
      posApi.createTransaction(data).then((r) => r.data.data),
    onSuccess: (_data, vars) => {
      qc.invalidateQueries({ queryKey: ['posTransactions', vars.pos_session_id] });
      qc.invalidateQueries({ queryKey: ['currentPosSession'] });
      qc.invalidateQueries({ queryKey: ['stockLevels'] });
    },
  });
}

export function useVoidTransaction() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => posApi.voidTransaction(id).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['posTransactions'] });
      qc.invalidateQueries({ queryKey: ['stockLevels'] });
    },
  });
}
