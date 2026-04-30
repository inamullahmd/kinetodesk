import { useEffect, useMemo, useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import {
  Boxes,
  Clock3,
  ReceiptText,
  Trophy,
} from 'lucide-react'
import DonutChart from '../../components/charts/DonutChart'
import PerformanceTrendChart from '../../components/charts/PerformanceTrendChart'
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

type TrendSeriesKey = 'revenue' | 'profit'

const TREND_REVENUE_COLOR = '#4F46E5'
const TREND_PROFIT_COLOR = '#55B86A'

function TrendLegendToggle({
  label,
  color,
  active,
  onClick,
  isDark,
}: {
  label: string
  color: string
  active: boolean
  onClick: () => void
  isDark: boolean
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      aria-pressed={active}
      className={[
        'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm transition',
        active
          ? isDark
            ? 'border-slate-700 bg-slate-900 text-slate-200'
            : 'border-slate-200 bg-white text-slate-700'
          : isDark
            ? 'border-slate-800 bg-slate-950 text-slate-500'
            : 'border-slate-200 bg-slate-50 text-slate-400',
      ].join(' ')}
    >
      <span
        className="h-2.5 w-2.5 rounded-full"
        style={{
          backgroundColor: color,
          opacity: active ? 1 : 0.45,
        }}
      />

      <span className={active ? '' : 'opacity-70'}>{label}</span>
    </button>
  )
}

function formatProductCategory(product: DashboardResponse['topSellingProduct']) {
  if (!product) return undefined

  const categoryParts = [
    product.category_name,
    product.sub_category_name,
  ].filter(Boolean)

  return categoryParts.length ? categoryParts.join(' / ') : 'Category unavailable'
}

export default function OverviewPage() {
  const { setHeaderRange, theme } = useOutletContext<DashboardOutletContext>()

  const [data, setData] = useState<DashboardResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const [trendPeriod, setTrendPeriod] =
    useState<'monthly' | 'quarterly'>('monthly')

  const [visibleTrendSeries, setVisibleTrendSeries] = useState<Record<TrendSeriesKey, boolean>>({
    revenue: true,
    profit: true,
  })

  const [channelChartMetric, setChannelChartMetric] =
    useState<'revenue' | 'orders'>('revenue')

  useEffect(() => {
    let active = true

    async function loadDashboard() {
      try {
        setLoading(true)
        setError(null)

        const response = await getDashboardOverview()

        if (!active) return

        setData(response)
        setHeaderRange(response.reportingPeriod ?? null)
      } catch {
        if (!active) return

        setHeaderRange(null)
        setError('Failed to load dashboard data.')
      } finally {
        if (active) {
          setLoading(false)
        }
      }
    }

    loadDashboard()

    return () => {
      active = false
      setHeaderRange(null)
    }
  }, [setHeaderRange])

  function toggleTrendSeries(series: TrendSeriesKey) {
    setVisibleTrendSeries((prev) => ({
      ...prev,
      [series]: !prev[series],
    }))
  }

  const monthlyRevenueCategories = useMemo(
    () => data?.last12MonthsRevenue?.map((item) => item.month) ?? [],
    [data]
  )

  const monthlyRevenueSeries = useMemo(
    () => data?.last12MonthsRevenue?.map((item) => toNumber(item.revenue)) ?? [],
    [data]
  )

  const monthlyProfitSeries = useMemo(
    () => data?.last12MonthsProfit?.map((item) => toNumber(item.profit)) ?? [],
    [data]
  )

  const quarterRevenueCategories = useMemo(
    () => data?.last8QuartersRevenue?.map((item) => item.quarter) ?? [],
    [data]
  )

  const quarterRevenueSeries = useMemo(
    () => data?.last8QuartersRevenue?.map((item) => toNumber(item.revenue)) ?? [],
    [data]
  )

  const quarterProfitSeries = useMemo(
    () => data?.last8QuartersProfit?.map((item) => toNumber(item.profit)) ?? [],
    [data]
  )

  const trendCategories = useMemo(
    () =>
      trendPeriod === 'monthly'
        ? monthlyRevenueCategories
        : quarterRevenueCategories,
    [trendPeriod, monthlyRevenueCategories, quarterRevenueCategories]
  )

  const trendRevenueSeries = useMemo(
    () =>
      trendPeriod === 'monthly'
        ? monthlyRevenueSeries
        : quarterRevenueSeries,
    [trendPeriod, monthlyRevenueSeries, quarterRevenueSeries]
  )

  const trendProfitSeries = useMemo(
    () =>
      trendPeriod === 'monthly'
        ? monthlyProfitSeries
        : quarterProfitSeries,
    [trendPeriod, monthlyProfitSeries, quarterProfitSeries]
  )

  const salesByChannelChartData = useMemo(
    () =>
      (data?.salesByChannel ?? []).map((item) => ({
        label: item.channel ?? 'unknown',
        revenue: item.total_revenue,
        orders: item.total_orders,
      })),
    [data]
  )

  if (loading) {
    return (
      <SectionCard
        title="Loading Overview"
        description="Pulling business summary data..."
        variant={theme}
      >
        <div className={`py-12 text-center text-sm ${theme === 'dark' ? 'text-slate-400' : 'text-slate-500'}`}>
          Loading dashboard...
        </div>
      </SectionCard>
    )
  }

  if (error || !data) {
    return (
      <SectionCard
        title="Overview Unavailable"
        description="The dashboard data could not be loaded."
        variant={theme}
      >
        <div className={`py-12 text-center text-sm ${theme === 'dark' ? 'text-rose-300' : 'text-rose-600'}`}>
          {error ?? 'Something went wrong.'}
        </div>
      </SectionCard>
    )
  }

  return (
    <div className="space-y-8">
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <StatCard
          title="Revenue"
          value={formatCompactNumber(data.revenueComparison.current_month_revenue)}
          secondaryValue={formatCurrency(data.revenueComparison.current_month_revenue)}
          change={formatPercent(data.revenueComparison.percentage_change)}
          positive={(data.revenueComparison.percentage_change ?? 0) >= 0}
          variant={theme}
          monoSecondary
        />

        <StatCard
          title="Quarter Revenue"
          value={formatCompactNumber(data.quarterComparison.current_quarter_revenue)}
          secondaryValue={formatCurrency(data.quarterComparison.current_quarter_revenue)}
          change={formatPercent(data.quarterComparison.percentage_change)}
          positive={(data.quarterComparison.percentage_change ?? 0) >= 0}
          variant={theme}
          monoSecondary
        />

        <StatCard
          title="Profit"
          value={formatCompactNumber(data.profitComparison.current_month_profit)}
          secondaryValue={formatCurrency(data.profitComparison.current_month_profit)}
          change={formatPercent(data.profitComparison.percentage_change)}
          positive={(data.profitComparison.percentage_change ?? 0) >= 0}
          variant={theme}
          monoSecondary
        />

        <StatCard
          title="Quarter Profit"
          value={formatCompactNumber(data.quarterProfitComparison.current_quarter_profit)}
          secondaryValue={formatCurrency(data.quarterProfitComparison.current_quarter_profit)}
          change={formatPercent(data.quarterProfitComparison.percentage_change)}
          positive={(data.quarterProfitComparison.percentage_change ?? 0) >= 0}
          variant={theme}
          monoSecondary
        />

        <StatCard
          title="Inventory Value"
          value={formatCompactNumber(data.currentInventoryValue)}
          secondaryValue={formatCurrency(data.currentInventoryValue)}
          helperText="Current stock valuation"
          variant={theme}
          monoSecondary
        />
      </div>

      <SectionCard
        title="Performance Trend"
        description="Revenue and profit over the selected period."
        action={
          <div className="flex flex-wrap items-center justify-end gap-3">
            <div className="flex items-center gap-2">
              <TrendLegendToggle
                label="Revenue"
                color={TREND_REVENUE_COLOR}
                active={visibleTrendSeries.revenue}
                onClick={() => toggleTrendSeries('revenue')}
                isDark={theme === 'dark'}
              />

              <TrendLegendToggle
                label="Profit"
                color={TREND_PROFIT_COLOR}
                active={visibleTrendSeries.profit}
                onClick={() => toggleTrendSeries('profit')}
                isDark={theme === 'dark'}
              />
            </div>

            <ChartToggle
              value={trendPeriod}
              onChange={(value) => setTrendPeriod(value)}
              options={[
                { label: 'Monthly', value: 'monthly' },
                { label: 'Quarterly', value: 'quarterly' },
              ]}
              variant={theme}
            />
          </div>
        }
        variant={theme}
      >
        <PerformanceTrendChart
          categories={trendCategories}
          revenue={trendRevenueSeries}
          profit={trendProfitSeries}
          visibleSeries={visibleTrendSeries}
          variant={theme}
          height={390}
        />
      </SectionCard>

      <div className="grid items-stretch gap-6 xl:grid-cols-[0.82fr_1.08fr_0.72fr]">
        <SectionCard
          title="Sales by Channel"
          description="Channel mix with revenue and order contribution."
          action={
            <ChartToggle
              value={channelChartMetric}
              onChange={(value) => setChannelChartMetric(value)}
              options={[
                { label: 'Revenue', value: 'revenue' },
                { label: 'Orders', value: 'orders' },
              ]}
              variant={theme}
            />
          }
          variant={theme}
        >
          <DonutChart
            items={salesByChannelChartData}
            metric={channelChartMetric}
            variant={theme}
          />
        </SectionCard>

        <SectionCard
          title="Low Stock Products"
          description="Items needing immediate replenishment attention."
          variant={theme}
        >
          <LowStockList items={data.lowStockProducts ?? []} variant={theme} />
        </SectionCard>

        <div className="grid content-start gap-4">
          <StatCard
            title="Top Employee"
            value={data.topEmployee ? data.topEmployee.employee_name : '-'}
            secondaryValue={
              data.topEmployee
                ? formatCurrency(data.topEmployee.total_sales)
                : undefined
            }
            helperText={
              data.topEmployee
                ? `${formatNumber(data.topEmployee.total_orders)} orders this month`
                : undefined
            }
            icon={<Trophy size={18} />}
            compact
            variant={theme}
          />

          <StatCard
            title="Top Product"
            value={data.topSellingProduct ? data.topSellingProduct.product_name : '-'}
            secondaryValue={
              data.topSellingProduct
                ? `${formatNumber(data.topSellingProduct.total_quantity)} sold`
                : undefined
            }
            helperText={formatProductCategory(data.topSellingProduct)}
            icon={<Boxes size={18} />}
            compact
            variant={theme}
          />

          <StatCard
            title="Pending Orders"
            value={formatNumber(data.pendingOrdersCount)}
            secondaryValue="Awaiting action"
            helperText="Needs immediate review"
            icon={<Clock3 size={18} />}
            compact
            variant={theme}
          />

          <StatCard
            title="Refunds"
            value={formatCompactNumber(data.refundSummary.refund_value)}
            secondaryValue={formatCurrency(data.refundSummary.refund_value)}
            helperText={`${formatNumber(data.refundSummary.refund_count)} refunds this month`}
            icon={<ReceiptText size={18} />}
            compact
            variant={theme}
            monoSecondary
          />
        </div>
      </div>
    </div>
  )
}