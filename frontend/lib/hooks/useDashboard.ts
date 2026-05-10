'use client';

import { useQuery } from '@tanstack/react-query';
import { dashboardApi } from '../api/dashboard';

export function useDashboardKpis() {
  return useQuery({
    queryKey: ['dashboardKpis'],
    queryFn:  () => dashboardApi.getKpis().then((r) => r.data.data),
  });
}

export function useRevenueTrend() {
  return useQuery({
    queryKey: ['revenueTrend'],
    queryFn:  () => dashboardApi.getRevenueTrend().then((r) => r.data.data),
  });
}

export function useTopProducts() {
  return useQuery({
    queryKey: ['dashTopProducts'],
    queryFn:  () => dashboardApi.getTopProducts().then((r) => r.data.data),
  });
}

export function useTopCustomers() {
  return useQuery({
    queryKey: ['dashTopCustomers'],
    queryFn:  () => dashboardApi.getTopCustomers().then((r) => r.data.data),
  });
}

export function useDashboardLowStock() {
  return useQuery({
    queryKey: ['dashLowStock'],
    queryFn:  () => dashboardApi.getLowStock().then((r) => r.data.data),
  });
}

export function useRecentActivity() {
  return useQuery({
    queryKey: ['recentActivity'],
    queryFn:  () => dashboardApi.getRecentActivity().then((r) => r.data.data),
  });
}
