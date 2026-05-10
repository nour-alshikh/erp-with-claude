import client from './client';

export const dashboardApi = {
  getKpis:           () => client.get('/reports/kpis'),
  getRevenueTrend:   () => client.get('/reports/revenue-trend'),
  getTopProducts:    () => client.get('/reports/top-products'),
  getTopCustomers:   () => client.get('/reports/top-customers'),
  getLowStock:       () => client.get('/reports/low-stock'),
  getRecentActivity: () => client.get('/reports/recent-activity'),
};
