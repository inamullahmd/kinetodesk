import { useEffect, useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import BarChart from '../../components/charts/BarChart'
import DonutChart from '../../components/charts/DonutChart'
import ChartToggle from '../../components/dashboard/ChartToggle'
import LowStockList from '../../components/dashboard/LowStockList'
import SectionCard from '../../components/dashboard/SectionCard'
import StatCard from '../../components/dashboard/StatCard'
import { getDashboardOverview } from '../../api/dashboard'
import type { DashboardResponse } from '../../types/dashboard'
import type { DashboardOutletContext } from '../../layouts/DashboardLayout'
import {
  formatCompactNumber,
  formatCurrency,
  formatNumber,
  formatPercent,
  toNumber,
} from '../../utils/format'

export default function OverviewPage() {
  const { setAsOfDate } = useOutletContext<DashboardOutletContext>()

  const [data, setData] = useState<DashboardResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const [monthlyChartMetric, setMonthlyChartMetric] =
    useState<'revenue' | 'profit'>('revenue')

  const [quarterlyChartMetric, setQuarterlyChartMetric] =
    useState<'revenue' | 'profit'>('revenue')

  const [channelChartMetric, setChannelChartMetric] =
    useState<'revenue' | 'orders'>('revenue')

  useEffect(() => {
    async function loadDashboard() {
      try {
        setLoading(true)
        setError(null)

        const response = await getDashboardOverview()
        setData(response)
        setAsOfDate(response.asOfDate ?? null)
      } catch {
        setError('Failed to load dashboard data.')
      } finally {
        setLoading(false)
      }
    }

    loadDashboard()
  }, [setAsOfDate])

  if (loading) {
    return (
      <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        Loading dashboard...
      </div>
    )
  }

  if (error || !data) {
    return (
      <div className="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-rose-700 shadow-sm">
        {error ?? 'Something went wrong.'}
      </div>
    )
  }

  const monthlyRevenueCategories = data.last12MonthsRevenue.map((item) => item.month)
  const monthlyRevenueSeries = data.last12MonthsRevenue.map((item) => toNumber(item.revenue))

  const monthlyProfitCategories = data.last12MonthsProfit.map((item) => item.month)
  const monthlyProfitSeries = data.last12MonthsProfit.map((item) => toNumber(item.profit))

  const quarterRevenueCategories = data.last8QuartersRevenue.map((item) => item.quarter)
  const quarterRevenueSeries = data.last8QuartersRevenue.map((item) => toNumber(item.revenue))

  const quarterProfitCategories = data.last8QuartersProfit.map((item) => item.quarter)
  const quarterProfitSeries = data.last8QuartersProfit.map((item) => toNumber(item.profit))

  const leftChartTitle =
    monthlyChartMetric === 'revenue'
      ? 'Revenue - Last 12 Months'
      : 'Profit - Last 12 Months'

  const leftChartCategories =
    monthlyChartMetric === 'revenue'
      ? monthlyRevenueCategories
      : monthlyProfitCategories

  const leftChartSeries =
    monthlyChartMetric === 'revenue'
      ? monthlyRevenueSeries
      : monthlyProfitSeries

  const leftChartSeriesName =
    monthlyChartMetric === 'revenue' ? 'Revenue' : 'Profit'

  const rightChartTitle =
    quarterlyChartMetric === 'revenue'
      ? 'Revenue - Last 8 Quarters'
      : 'Profit - Last 8 Quarters'

  const rightChartCategories =
    quarterlyChartMetric === 'revenue'
      ? quarterRevenueCategories
      : quarterProfitCategories

  const rightChartSeries =
    quarterlyChartMetric === 'revenue'
      ? quarterRevenueSeries
      : quarterProfitSeries

  const rightChartSeriesName =
    quarterlyChartMetric === 'revenue' ? 'Revenue' : 'Profit'

  const salesByChannelChartData = data.salesByChannel.map((item) => ({
    label: item.channel ?? 'unknown',
    revenue: item.total_revenue,
    orders: item.total_orders,
  }))

  return (
    <div className="space-y-8">
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <StatCard
          title="Revenue"
          value={formatCompactNumber(data.revenueComparison.current_month_revenue)}
          secondaryValue={formatCurrency(data.revenueComparison.current_month_revenue)}
          change={formatPercent(data.revenueComparison.percentage_change)}
          positive={(data.revenueComparison.percentage_change ?? 0) >= 0}
        />

        <StatCard
          title="Quarter Revenue"
          value={formatCompactNumber(data.quarterComparison.current_quarter_revenue)}
          secondaryValue={formatCurrency(data.quarterComparison.current_quarter_revenue)}
          change={formatPercent(data.quarterComparison.percentage_change)}
          positive={(data.quarterComparison.percentage_change ?? 0) >= 0}
        />

        <StatCard
          title="Profit"
          value={formatCompactNumber(data.profitComparison.current_month_profit)}
          secondaryValue={formatCurrency(data.profitComparison.current_month_profit)}
          change={formatPercent(data.profitComparison.percentage_change)}
          positive={(data.profitComparison.percentage_change ?? 0) >= 0}
        />

        <StatCard
          title="Quarter Profit"
          value={formatCompactNumber(data.quarterProfitComparison.current_quarter_profit)}
          secondaryValue={formatCurrency(data.quarterProfitComparison.current_quarter_profit)}
          change={formatPercent(data.quarterProfitComparison.percentage_change)}
          positive={(data.quarterProfitComparison.percentage_change ?? 0) >= 0}
        />

        <StatCard
          title="Inventory Value"
          value={formatCompactNumber(data.currentInventoryValue)}
          secondaryValue={formatCurrency(data.currentInventoryValue)}
        />
      </div>

      <div className="grid items-stretch gap-6 xl:grid-cols-12">
        <div className="min-w-0 xl:col-span-6">
          <SectionCard
            title={leftChartTitle}
            action={
              <ChartToggle
                value={monthlyChartMetric}
                onChange={setMonthlyChartMetric}
                options={[
                  { label: 'Revenue', value: 'revenue' },
                  { label: 'Profit', value: 'profit' },
                ]}
              />
            }
          >
            <BarChart
              categories={leftChartCategories}
              data={leftChartSeries}
              seriesName={leftChartSeriesName}
            />
            <p className="mt-2 text-xs text-slate-400 md:hidden">
              Swipe horizontally to view full chart
            </p>
          </SectionCard>
        </div>

        <div className="min-w-0 xl:col-span-6">
          <SectionCard
            title={rightChartTitle}
            action={
              <ChartToggle
                value={quarterlyChartMetric}
                onChange={setQuarterlyChartMetric}
                options={[
                  { label: 'Revenue', value: 'revenue' },
                  { label: 'Profit', value: 'profit' },
                ]}
              />
            }
          >
            <BarChart
              categories={rightChartCategories}
              data={rightChartSeries}
              seriesName={rightChartSeriesName}
            />
            <p className="mt-2 text-xs text-slate-400 md:hidden">
              Swipe horizontally to view full chart
            </p>
          </SectionCard>
        </div>
      </div>

      <div className="grid gap-6 xl:grid-cols-2">
        <SectionCard
          title="Sales by Channel"
          action={
            <ChartToggle
              value={channelChartMetric}
              onChange={setChannelChartMetric}
              options={[
                { label: 'Revenue', value: 'revenue' },
                { label: 'Orders', value: 'orders' },
              ]}
            />
          }
        >
          <DonutChart
            items={salesByChannelChartData}
            metric={channelChartMetric}
          />
        </SectionCard>

        <SectionCard title="Low Stock Products">
          <LowStockList items={data.lowStockProducts} />
        </SectionCard>
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <StatCard
          title="Top Employee"
          value={data.topEmployee ? data.topEmployee.employee_name : '-'}
          secondaryValue={
            data.topEmployee
              ? `${formatCurrency(data.topEmployee.total_sales)} · ${formatNumber(
                  data.topEmployee.total_orders
                )} orders`
              : undefined
          }
          compact
        />

        <StatCard
          title="Top Product"
          value={data.topSellingProduct ? data.topSellingProduct.product_name : '-'}
          secondaryValue={
            data.topSellingProduct
              ? `${formatNumber(data.topSellingProduct.total_quantity)} sold`
              : undefined
          }
          compact
        />

        <StatCard
          title="Pending Orders"
          value={formatNumber(data.pendingOrdersCount)}
          secondaryValue="Awaiting action"
          compact
        />

        <StatCard
          title="Refunds"
          value={formatCompactNumber(data.refundSummary.refund_value)}
          secondaryValue={`${formatCurrency(data.refundSummary.refund_value)} · ${formatNumber(data.refundSummary.refund_count)} refunds`}
          compact
        />
      </div>
    </div>
  )
}