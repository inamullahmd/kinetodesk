import { useEffect, useState } from 'react';
import OrderStatusChart from '@/components/charts/OrderStatusChart';
import RevenueTrendChart from '@/components/charts/RevenueTrendChart';
import SalesByCategoryChart from '@/components/charts/SalesByCategoryChart';
import SalesByCustomerTypeChart from '@/components/charts/SalesByCustomerTypeChart';
import SalesByRegionChart from '@/components/charts/SalesByRegionChart';
import TopProductsChart from '@/components/charts/TopProductsChart';
import DashboardShell from '@/components/layout/DashboardShell';
import KpiCard from '@/components/ui/KpiCard';
import { api } from '@/services/api';
import type {
  CategorySalesPoint,
  CustomerTypeSalesPoint,
  DateRangeOption,
  OverviewKpis,
  RegionSalesPoint,
  RevenueTrendPoint,
  StatusPoint,
  TopProductPoint,
} from '@/types/overview';

type OverviewResponse = {
  kpis: OverviewKpis;
  revenue_trend: RevenueTrendPoint[];
  order_status: StatusPoint[];
  top_products: TopProductPoint[];
  sales_by_category: CategorySalesPoint[];
  sales_by_region: RegionSalesPoint[];
  sales_by_customer_type: CustomerTypeSalesPoint[];
  meta: {
    start_date: string;
    end_date: string;
  };
};

function getDateRangeFromPreset(range: Exclude<DateRangeOption, 'custom'>) {
  const end = new Date();
  const start = new Date(end);

  switch (range) {
    case '1m':
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

function formatCurrency(value: number) {
  return `$${value.toLocaleString()}`;
}

function toTitleCase(value: string) {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

export default function OverviewPage() {
  const initialPreset = getDateRangeFromPreset('3m');

  const [kpis, setKpis] = useState<OverviewKpis | null>(null);
  const [trend, setTrend] = useState<RevenueTrendPoint[]>([]);
  const [statusData, setStatusData] = useState<StatusPoint[]>([]);
  const [topProductsData, setTopProductsData] = useState<TopProductPoint[]>([]);
  const [categoryData, setCategoryData] = useState<CategorySalesPoint[]>([]);
  const [regionData, setRegionData] = useState<RegionSalesPoint[]>([]);
  const [customerTypeData, setCustomerTypeData] = useState<CustomerTypeSalesPoint[]>([]);
  const [loading, setLoading] = useState(true);

  const [range, setRange] = useState<DateRangeOption>('3m');
  const [startDate, setStartDate] = useState<string>(toInputDate(initialPreset.start));
  const [endDate, setEndDate] = useState<string>(toInputDate(initialPreset.end));

  useEffect(() => {
    let active = true;

    async function loadOverview() {
      try {
        setLoading(true);

        const { data } = await api.get<OverviewResponse>('/overview', {
          params: { start_date: startDate, end_date: endDate },
        });

        if (!active) return;

        setKpis(data.kpis);
        setTrend(data.revenue_trend);
        setStatusData(data.order_status);
        setTopProductsData(data.top_products);
        setCategoryData(data.sales_by_category);
        setRegionData(data.sales_by_region);
        setCustomerTypeData(data.sales_by_customer_type);
      } catch (error) {
        console.error('Failed to load overview data:', error);
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    }

    loadOverview();

    return () => {
      active = false;
    };
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

  const topProduct = topProductsData[0] ?? null;
  const topCategory = categoryData[0] ?? null;
  const leadingStatus = statusData[0] ?? null;

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
        <div className="rounded-[24px] border border-black/6 bg-white p-6 text-sm text-slate-500 shadow-[0_1px_2px_rgba(15,23,42,0.04)] dark:border-white/6 dark:bg-[#151a24] dark:text-slate-400">
          Loading dashboard...
        </div>
      ) : (
        <div className="space-y-4">
          <section className="grid grid-cols-1 gap-3 md:grid-cols-2 2xl:grid-cols-6">
            <KpiCard
              title="Total Revenue"
              value={formatCurrency(kpis.total_revenue)}
              hint="Completed sales in selected period"
            />
            <KpiCard
              title="Total Orders"
              value={kpis.total_orders.toLocaleString()}
              hint="Orders in selected period"
            />
            <KpiCard
              title="Avg Order Value"
              value={formatCurrency(kpis.average_order_value)}
              hint="Revenue per completed order"
            />
            <KpiCard
              title="Units Sold"
              value={kpis.units_sold.toLocaleString()}
              hint="Units sold in selected period"
            />
            <KpiCard
              title="Purchase Spend"
              value={formatCurrency(kpis.purchase_spend)}
              hint="Received purchase orders in selected period"
            />
            <KpiCard
              title="Low Stock Count"
              value={kpis.low_stock_count}
              hint="Current items at risk"
            />
          </section>

          <section className="grid grid-cols-1 gap-4 xl:grid-cols-12">
            <div className="xl:col-span-8">
              <RevenueTrendChart data={trend} />
            </div>

            <div className="xl:col-span-4">
              <div className="flex h-full flex-col rounded-[24px] border border-black/6 bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)] dark:border-white/6 dark:bg-[#151a24]">
                <p className="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                  Snapshot
                </p>
                <h2 className="mt-1 text-[1.35rem] font-bold tracking-[-0.03em] text-slate-950 dark:text-white">
                  Operational highlights
                </h2>

                <div className="mt-4 grid grid-cols-1 gap-3">
                  <div className="rounded-[20px] border border-indigo-200/70 bg-indigo-50 px-4 py-3.5 dark:border-indigo-500/20 dark:bg-indigo-500/10">
                    <div className="text-[10px] font-semibold uppercase tracking-[0.14em] text-indigo-700 dark:text-indigo-300">
                      Top Product
                    </div>
                    <div className="mt-2 text-[1rem] font-semibold text-slate-950 dark:text-white">
                      {topProduct?.name ?? '—'}
                    </div>
                    <p className="mt-1 text-[12px] text-slate-600 dark:text-slate-300">
                      {topProduct
                        ? `${formatCurrency(Number(topProduct.revenue))} in revenue during the selected period.`
                        : 'No product sales recorded in the selected period.'}
                    </p>
                  </div>

                  <div className="rounded-[20px] border border-emerald-200/70 bg-emerald-50 px-4 py-3.5 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                    <div className="text-[10px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:text-emerald-300">
                      Top Category
                    </div>
                    <div className="mt-2 text-[1rem] font-semibold text-slate-950 dark:text-white">
                      {topCategory?.name ?? '—'}
                    </div>
                    <p className="mt-1 text-[12px] text-slate-600 dark:text-slate-300">
                      {topCategory
                        ? `${formatCurrency(Number(topCategory.value))} generated in the selected period.`
                        : 'No category revenue recorded in the selected period.'}
                    </p>
                  </div>

                  <div className="rounded-[20px] border border-amber-200/70 bg-amber-50 px-4 py-3.5 dark:border-amber-500/20 dark:bg-amber-500/10">
                    <div className="text-[10px] font-semibold uppercase tracking-[0.14em] text-amber-700 dark:text-amber-300">
                      Order Mix
                    </div>
                    <div className="mt-2 text-[1rem] font-semibold text-slate-950 dark:text-white">
                      {leadingStatus ? `${toTitleCase(leadingStatus.name)} leads` : '—'}
                    </div>
                    <p className="mt-1 text-[12px] text-slate-600 dark:text-slate-300">
                      {leadingStatus
                        ? `${leadingStatus.value.toLocaleString()} orders in the largest status bucket.`
                        : 'No order status data available for the selected period.'}
                    </p>
                  </div>
                </div>

                <div className="mt-4 rounded-[20px] border border-slate-200 bg-slate-50 px-4 py-3.5 dark:border-white/8 dark:bg-white/[0.03]">
                  <p className="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600 dark:text-slate-400">
                    Store Health
                  </p>
                  <p className="mt-2 text-[12px] leading-6 text-slate-700 dark:text-slate-200">
                    Revenue and demand are being led by{' '}
                    <span className="font-semibold text-slate-950 dark:text-white">
                      {topCategory?.name ?? 'the current top category'}
                    </span>
                    , while{' '}
                    <span className="font-semibold text-slate-950 dark:text-white">
                      {topProduct?.name ?? 'the top product'}
                    </span>{' '}
                    is the strongest SKU in the selected period. Low-stock exposure remains at{' '}
                    <span className="font-semibold text-slate-950 dark:text-white">
                      {kpis.low_stock_count}
                    </span>{' '}
                    items and should be monitored operationally.
                  </p>
                </div>
              </div>
            </div>
          </section>

          <section className="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <TopProductsChart data={topProductsData} />
            <SalesByCategoryChart data={categoryData} />
            <OrderStatusChart data={statusData} />
          </section>

          <section className="grid grid-cols-1 gap-4 2xl:grid-cols-2">
            <SalesByRegionChart data={regionData} />
            <SalesByCustomerTypeChart data={customerTypeData} />
          </section>
        </div>
      )}
    </DashboardShell>
  );
}