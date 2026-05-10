'use client';

import {
  useDashboardKpis,
  useRevenueTrend,
  useTopProducts,
  useTopCustomers,
  useDashboardLowStock,
  useRecentActivity,
} from '@/lib/hooks/useDashboard';
import { formatCents } from '@/lib/utils/money';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import {
  LineChart,
  Line,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from 'recharts';

function chartMoney(cents: number): string {
  const dollars = cents / 100;
  if (dollars >= 1_000_000) return `$${(dollars / 1_000_000).toFixed(1)}M`;
  if (dollars >= 1_000)     return `$${(dollars / 1_000).toFixed(0)}K`;
  return `$${dollars.toFixed(0)}`;
}

function KpiCard({
  label,
  value,
  loading,
  colorClass = 'text-foreground',
}: {
  label: string;
  value: number;
  loading: boolean;
  colorClass?: string;
}) {
  return (
    <div className="border rounded-xl p-5 bg-background">
      <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">{label}</p>
      {loading ? (
        <Skeleton className="h-8 w-32 mt-2" />
      ) : (
        <p className={`text-2xl font-bold mt-1 ${colorClass}`}>{formatCents(value)}</p>
      )}
    </div>
  );
}

const statusVariant = (s: string): 'default' | 'secondary' | 'outline' | 'destructive' => {
  if (['paid', 'completed'].includes(s)) return 'default';
  if (['unpaid', 'voided'].includes(s))  return 'destructive';
  return 'secondary';
};

export default function DashboardPage() {
  const { data: kpis,      isLoading: kpisLoading }      = useDashboardKpis();
  const { data: trend,     isLoading: trendLoading }     = useRevenueTrend();
  const { data: products,  isLoading: productsLoading }  = useTopProducts();
  const { data: customers, isLoading: customersLoading } = useTopCustomers();
  const { data: lowStock,  isLoading: lowStockLoading }  = useDashboardLowStock();
  const { data: activity,  isLoading: activityLoading }  = useRecentActivity();

  const maxCustomerRevenue = customers?.[0]?.revenue ?? 1;

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Dashboard</h1>

      {/* ── KPI Cards ─────────────────────────────────────────────────── */}
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <KpiCard
          label="Revenue MTD"
          value={kpis?.revenue_mtd ?? 0}
          loading={kpisLoading}
          colorClass="text-green-600 dark:text-green-400"
        />
        <KpiCard
          label="Expenses MTD"
          value={kpis?.expenses_mtd ?? 0}
          loading={kpisLoading}
          colorClass="text-red-600 dark:text-red-400"
        />
        <KpiCard
          label="Net Profit MTD"
          value={Math.abs(kpis?.net_profit_mtd ?? 0)}
          loading={kpisLoading}
          colorClass={
            (kpis?.net_profit_mtd ?? 0) >= 0
              ? 'text-blue-600 dark:text-blue-400'
              : 'text-orange-600 dark:text-orange-400'
          }
        />
        <KpiCard
          label="Outstanding AR"
          value={kpis?.outstanding_ar ?? 0}
          loading={kpisLoading}
          colorClass="text-amber-600 dark:text-amber-400"
        />
        <KpiCard
          label="Outstanding AP"
          value={kpis?.outstanding_ap ?? 0}
          loading={kpisLoading}
          colorClass="text-purple-600 dark:text-purple-400"
        />
      </div>

      {/* ── Revenue Trend ─────────────────────────────────────────────── */}
      <div className="border rounded-xl p-5 bg-background">
        <h2 className="font-semibold mb-4">Revenue — Last 12 Months</h2>
        {trendLoading ? (
          <Skeleton className="h-52 w-full" />
        ) : (
          <ResponsiveContainer width="100%" height={220}>
            <LineChart data={trend ?? []} margin={{ top: 4, right: 16, left: 0, bottom: 0 }}>
              <CartesianGrid strokeDasharray="3 3" stroke="hsl(var(--border))" />
              <XAxis dataKey="month" tick={{ fontSize: 11 }} />
              <YAxis tickFormatter={chartMoney} tick={{ fontSize: 11 }} width={64} />
              <Tooltip
                formatter={(v: number) => [formatCents(v), 'Revenue']}
                contentStyle={{ fontSize: 12 }}
              />
              <Line
                type="monotone"
                dataKey="revenue"
                stroke="#2563eb"
                strokeWidth={2}
                dot={{ r: 3 }}
                activeDot={{ r: 5 }}
              />
            </LineChart>
          </ResponsiveContainer>
        )}
      </div>

      {/* ── Top Products + Top Customers ──────────────────────────────── */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* Top Products */}
        <div className="border rounded-xl p-5 bg-background">
          <h2 className="font-semibold mb-4">Top 5 Products by Revenue</h2>
          {productsLoading ? (
            <Skeleton className="h-44 w-full" />
          ) : products?.length ? (
            <ResponsiveContainer width="100%" height={180}>
              <BarChart
                data={products}
                layout="vertical"
                margin={{ top: 0, right: 16, left: 0, bottom: 0 }}
              >
                <CartesianGrid strokeDasharray="3 3" stroke="hsl(var(--border))" />
                <XAxis type="number" tickFormatter={chartMoney} tick={{ fontSize: 10 }} />
                <YAxis
                  type="category"
                  dataKey="product"
                  width={110}
                  tick={{ fontSize: 11 }}
                />
                <Tooltip
                  formatter={(v: number) => [formatCents(v), 'Revenue']}
                  contentStyle={{ fontSize: 12 }}
                />
                <Bar dataKey="revenue" fill="#2563eb" radius={[0, 4, 4, 0]} />
              </BarChart>
            </ResponsiveContainer>
          ) : (
            <p className="text-sm text-muted-foreground py-10 text-center">No sales data yet.</p>
          )}
        </div>

        {/* Top Customers */}
        <div className="border rounded-xl p-5 bg-background">
          <h2 className="font-semibold mb-4">Top 5 Customers by Revenue</h2>
          {customersLoading ? (
            <Skeleton className="h-44 w-full" />
          ) : customers?.length ? (
            <div className="space-y-4 pt-1">
              {customers.map((c: any, i: number) => (
                <div key={i} className="flex items-center gap-3">
                  <span className="text-sm font-bold text-muted-foreground w-5 shrink-0">
                    {i + 1}
                  </span>
                  <div className="flex-1 min-w-0">
                    <div className="flex justify-between items-baseline mb-1">
                      <p className="text-sm font-medium truncate">{c.customer}</p>
                      <p className="text-sm font-semibold ml-2 shrink-0">{formatCents(c.revenue)}</p>
                    </div>
                    <div className="h-1.5 bg-muted rounded-full">
                      <div
                        className="h-1.5 bg-blue-500 rounded-full transition-all"
                        style={{
                          width: `${Math.round((c.revenue / maxCustomerRevenue) * 100)}%`,
                        }}
                      />
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-sm text-muted-foreground py-10 text-center">No invoice data yet.</p>
          )}
        </div>
      </div>

      {/* ── Low Stock + Recent Activity ───────────────────────────────── */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* Low Stock Alerts */}
        <div className="border rounded-xl p-5 bg-background">
          <h2 className="font-semibold mb-4">Low Stock Alerts</h2>
          {lowStockLoading ? (
            <Skeleton className="h-40 w-full" />
          ) : lowStock?.length ? (
            <div className="divide-y">
              {lowStock.map((item: any, i: number) => (
                <div key={i} className="flex items-center justify-between gap-2 py-2 text-sm">
                  <div className="min-w-0">
                    <p className="font-medium truncate">{item.product_name}</p>
                    <p className="text-xs text-muted-foreground">{item.warehouse_name}</p>
                  </div>
                  <div className="text-right shrink-0">
                    <span className="text-destructive font-bold text-base">{item.qty}</span>
                    <span className="text-muted-foreground text-xs"> / {item.reorder_point} min</span>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="py-10 text-center">
              <p className="text-sm text-green-600 font-medium">All stock levels healthy</p>
            </div>
          )}
        </div>

        {/* Recent Transactions */}
        <div className="border rounded-xl p-5 bg-background">
          <h2 className="font-semibold mb-4">Recent Transactions</h2>
          {activityLoading ? (
            <Skeleton className="h-40 w-full" />
          ) : activity?.length ? (
            <div className="divide-y">
              {activity.map((item: any, i: number) => (
                <div key={i} className="flex items-center gap-3 py-2 text-sm">
                  <div className="flex-1 min-w-0">
                    <p className="font-mono text-xs text-muted-foreground">{item.reference}</p>
                    <p className="font-medium truncate">{item.party}</p>
                  </div>
                  <div className="text-right shrink-0">
                    <p className="font-semibold">{formatCents(item.amount)}</p>
                    <Badge variant={statusVariant(item.status)} className="text-xs mt-0.5">
                      {item.status}
                    </Badge>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-sm text-muted-foreground py-10 text-center">No recent transactions.</p>
          )}
        </div>
      </div>
    </div>
  );
}
