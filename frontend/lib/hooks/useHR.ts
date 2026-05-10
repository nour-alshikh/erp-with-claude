'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  hrApi,
  type DepartmentPayload,
  type EmployeePayload,
  type LeaveRequestPayload,
  type RunPayrollPayload,
} from '../api/hr';

export function useDepartments() {
  return useQuery({
    queryKey: ['departments'],
    queryFn: () => hrApi.getDepartments().then((r) => r.data.data),
  });
}

export function usePositions() {
  return useQuery({
    queryKey: ['positions'],
    queryFn: () => hrApi.getPositions().then((r) => r.data.data),
  });
}

export function useEmployees() {
  return useQuery({
    queryKey: ['employees'],
    queryFn: () => hrApi.getEmployees().then((r) => r.data.data),
  });
}

export function useEmployee(id: number) {
  return useQuery({
    queryKey: ['employees', id],
    queryFn: () => hrApi.getEmployee(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateEmployee() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: EmployeePayload) => hrApi.createEmployee(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['employees'] }),
  });
}

export function useUpdateEmployee(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<EmployeePayload>) =>
      hrApi.updateEmployee(id, data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['employees'] });
      qc.invalidateQueries({ queryKey: ['employees', id] });
    },
  });
}

export function useDeleteEmployee() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => hrApi.deleteEmployee(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['employees'] }),
  });
}

export function useAttendance(employeeId: number, month: string) {
  return useQuery({
    queryKey: ['attendance', employeeId, month],
    queryFn: () =>
      hrApi.getAttendance({ employee_id: employeeId, month }).then((r) => r.data.data),
    enabled: !!employeeId,
  });
}

export function useLeaveTypes() {
  return useQuery({
    queryKey: ['leaveTypes'],
    queryFn: () => hrApi.getLeaveTypes().then((r) => r.data.data),
  });
}

export function useLeaves() {
  return useQuery({
    queryKey: ['leaves'],
    queryFn: () => hrApi.getLeaves().then((r) => r.data.data),
  });
}

export function useCreateLeave() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: LeaveRequestPayload) =>
      hrApi.createLeave(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['leaves'] }),
  });
}

export function useApproveLeave() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => hrApi.approveLeave(id).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['leaves'] }),
  });
}

export function useRejectLeave() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => hrApi.rejectLeave(id).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['leaves'] }),
  });
}

export function usePayrolls() {
  return useQuery({
    queryKey: ['payrolls'],
    queryFn: () => hrApi.getPayrolls().then((r) => r.data.data),
  });
}

export function usePayroll(id: number) {
  return useQuery({
    queryKey: ['payrolls', id],
    queryFn: () => hrApi.getPayroll(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useRunPayroll() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: RunPayrollPayload) =>
      hrApi.runPayroll(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['payrolls'] }),
  });
}

export function useApprovePayroll() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => hrApi.approvePayroll(id).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['payrolls'] }),
  });
}
