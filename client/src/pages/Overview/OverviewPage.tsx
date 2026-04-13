import { useEffect, useState } from 'react';
import RevenueTrendChart from '@/components/charts/RevenueTrendChart';
import DashboardShell from '@/components/layout/DashboardShell';
import KpiCard from '@/components/ui/KpiCard';
import LowStockTable from '@/components/ui/LowStockTable';
import RecentSalesTable from '@/components/ui/RecentSalesTable';
import { api } from '@/services/api';
import type {
  DateRangeOption,
  LowStockItem,
  OverviewKpis,
  RecentSale,
  RevenueTrendPoint,
} from '@/types/overview';

function getDateRangeFromPreset(range: Exclude<DateRangeOption, 'custom'>) {
  const end = new Date();
  const start = new Date(end);

  switch (range) {
    case '30d':
      start.setMonth(end.getMonth() - 1);
      break;
    case '3m':
      start.setMonth(end.getMonth() - 3);
      break;
    case '6m':
      start.setMonth(end.getMonth() - 6);
      break;
    case '12m':
      start.setFullYear(end.getFullYear() - 1);
      break;
  }

  return { start, end };
}

function toInputDate(value: Date) {
  return value.toISOString().split('T')[0];
}

export default function OverviewPage() {
  const initialPreset = getDateRangeFromPreset('6m');

  const [kpis, setKpis] = useState<OverviewKpis | null>(null);
  const [trend, setTrend] = useState<RevenueTrendPoint[]>([]);
  const [lowStock, setLowStock] = useState<LowStockItem[]>([]);
  const [recentSales, setRecentSales] = useState<RecentSale[]>([]);
  const [loading, setLoading] = useState(true);

  const [range, setRange] = useState<DateRangeOption>('6m');
  const [startDate, setStartDate] = useState<string>(toInputDate(initialPreset.start));
  const [endDate, setEndDate] = useState<string>(toInputDate(initialPreset.end));

  useEffect(() => {
    async function loadOverview() {
      try {
        setLoading(true);

        const [kpisRes, trendRes, lowStockRes, recentSalesRes] = await Promise.all([
          api.get('/overview/kpis', {
            params: { start_date: startDate, end_date: endDate },
          }),
          api.get('/overview/revenue-trend', {
            params: { start_date: startDate, end_date: endDate },
          }),
          api.get('/overview/low-stock'),
          api.get('/overview/recent-sales'),
        ]);

        setKpis(kpisRes.data);
        setTrend(trendRes.data);
        setLowStock(lowStockRes.data);
        setRecentSales(recentSalesRes.data);
      } catch (error) {
        console.error('Failed to load overview data:', error);
      } finally {
        setLoading(false);
      }
    }

    loadOverview();
  }, [startDate, endDate]);

  function handleRangeChange(nextRange: DateRangeOption) {
    setRange(nextRange);

    if (nextRange === 'custom') {
      return;
    }

    const { start, end } = getDateRangeFromPreset(nextRange);
    setStartDate(toInputDate(start));
    setEndDate(toInputDate(end));
  }

  function handleApplyCustomRange() {
    if (!startDate || !endDate) return;
    if (new Date(startDate) > new Date(endDate)) return;

    setRange('custom');
    setStartDate(startDate);
    setEndDate(endDate);
  }

  return (
    <DashboardShell
      range={range}
      onRangeChange={handleRangeChange}
      customStartDate={startDate}
      customEndDate={endDate}
      onCustomStartDateChange={setStartDate}
      onCustomEndDateChange={setEndDate}
      onApplyCustomRange={handleApplyCustomRange}
    >
      {loading || !kpis ? (
        <div className="rounded-3xl border border-slate-200 bg-white p-8 text-sm text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
          Loading dashboard...
        </div>
      ) : (
        <div className="space-y-6">
          <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <KpiCard
              title="Total Revenue"
              value={`$${kpis.total_revenue.toLocaleString()}`}
              hint="Completed sales only"
            />
            <KpiCard
              title="Total Orders"
              value={kpis.total_orders.toLocaleString()}
              hint="Completed order count"
            />
            <KpiCard
              title="Average Order Value"
              value={`$${kpis.average_order_value.toLocaleString()}`}
              hint="Revenue per completed order"
            />
            <KpiCard
              title="Low Stock Items"
              value={kpis.low_stock_count}
              hint="At or below reorder threshold"
            />
          </section>

          <section>
            <RevenueTrendChart data={trend} />
          </section>

          <section className="grid grid-cols-1 gap-6 2xl:grid-cols-2">
            <LowStockTable items={lowStock} />
            <RecentSalesTable items={recentSales} />
          </section>
        </div>
      )}
    </DashboardShell>
  );
}